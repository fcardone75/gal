<?php

namespace App\Service\Export;

use App\Entity\AdditionalContribution;
use App\Entity\Application;
use App\Entity\ApplicationImport;
use App\Entity\ApplicationImportTemplate;
use App\Entity\AssuranceEnterpriseImport;
use App\Entity\FinancingImport;
use App\Entity\LeasingImport;
use App\Error\Import\ImportError;
use App\Service\Contracts\Export\ApplicationExportManagerInterface;
use App\Service\Contracts\Export\Config\YamlFileLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApplicationExportManager implements ApplicationExportManagerInterface
{
    use ImporterTrait;

    /** @var IOFactory */
    private $adapter;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var TranslatorInterface  */
    private $translator;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $config;

    /** @var string */
    private $configFilePath;

    /** @var array */
    private $entities = [];

    /** @var ImportError[] */
    private $errors = [];

    private $handle;

    /**
     * ApplicationImporter constructor.
     * @param YamlFileLoaderInterface $yamlFileLoader
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param ValidatorInterface $validator
     * @param LoggerInterface $logger
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function __construct(
        YamlFileLoaderInterface $yamlFileLoader,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->adapter = IOFactory::createReader('Xlsx');
        $this->configFilePath = $yamlFileLoader->getLocator()->locate('application_export_csv.yaml');
        $this->config = $yamlFileLoader->load('application_export_csv.yaml');
        if (!$this->hasValidConfig()) {
            throw new \Exception(sprintf('The configuration in "%s" is not valid', $this->configFilePath));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createApplicationsCsv($applications): StreamedResponse
    {
        $response = new StreamedResponse();
//TODO: verificare nome file export
        $filename = 'export_application.csv';

        $entities = $applications;


        $response->setCallback(function () use ($entities, $filename) {

            $config = $this->getConfig();
            $application_fields = $config['App\Entity\Application']['fields'];
            $labels['application'] = array_values($application_fields);
            $fields['application'] = array_keys($application_fields);

            $additional_contribution_fields = $config['App\Entity\AdditionalContribution']['fields'];
            $labels['additional_contribution'] = [];
            $fields['additional_contribution'] = array_keys($additional_contribution_fields);

//TODO: metodo per elenco in entity?
            $additionalContributionTypes = [AdditionalContribution::TYPE_ABB, AdditionalContribution::TYPE_CON, AdditionalContribution::TYPE_CFP];

//TODO: aggiungo suffisso a label in base a tipo
            foreach($additionalContributionTypes as $type) {
                foreach($additional_contribution_fields as $value) {
                    $labels['additional_contribution'][] = $type . ' - ' . $value;
                }
            }

            $labels_tot = [];
            foreach($labels as $labels_group) {
                foreach($labels_group as $label) {
                    $labels_tot[] = $label;
                }
            }
//dd($labels_tot);

            $this->handle = fopen('php://output', 'w+');
            $csv_delimiter = ';';
//            $eol = "\r\n";

            fputcsv($this->handle, $labels_tot, $csv_delimiter);
//            $this->add_eol($this->handle, $this->eol);
//            $this->add_eol($this->handle, $eol);

            foreach($entities as $entity) {
                $row = [];
                foreach ($fields['application'] as $field) {
                    $getter = $this->getterForProperty($field);
                    $value = $entity->{$getter}();
                    switch($field) {
                        case 'status':
                            $value = $this->translator->trans($value, [], 'application_status');
                            break;
//                        default:
//                            $value = $entity->{$getter}();
                    }
                    $row[] = $this->getFormattedValue($value);
                }

//TODO: aggiungo colonne additional contribution (ogni contributo x colonne)
                $additionalContributions = $entity->getAdditionalContributions();

                foreach($additionalContributionTypes as $type) {
                    $additionalContributionsTmp = $entity->getAdditionalContributionsOfType(
                        $additionalContributions,
                        $type
                    );
                    foreach ($fields['additional_contribution'] as $field) {
                        $getter = $this->getterForProperty($field);
                        $entityTmp = $additionalContributionsTmp->first();
                        if (!empty($entityTmp)) {
                            $value = $entityTmp->{$getter}();
                            switch($field) {
                                case 'id':
                                    $value = 'X';
                                    break;
                                case 'nsiaStatus':
                                    $value = $this->translator->trans($value, [], 'additional_contribution_status');
                                    break;
//                                default:
//                                    $value = $entityTmp->{$getter}();
                            }
                        } else {
                            $value = '';
                        }
                        $row[] = $this->getFormattedValue($value);
                    }
                }

                fputcsv($this->handle, $row, $csv_delimiter);
//                $this->add_eol($this->handle, $eol);
            }
            fclose($this->handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

//    protected function add_eol($fp, $eol = null) {
//        fseek($fp, -1, SEEK_CUR);
//        // write out a CR/LF
//        fwrite($fp, $eol);
//    }

    protected function getFormattedValue($value)
    {
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d');
        };
        return $value;
    }


    /**
     * {@inheritDoc}
     */
    public function getConfig(): array
    {
        return $this->config;
    }


    protected function hasValidConfig(): bool
    {
        if (!isset($this->config['App\Entity\Application']['fields']) || !$this->config['App\Entity\Application']['fields'] || !is_array($this->config['App\Entity\Application']['fields'])) {
            $this->logger->error('The configuration file "{filepath}" is missing the "fields" configuration node, the node is empty or it is not an array', [
                'filepath' => $this->configFilePath
            ]);
            return false;
        }
        return true;
    }

}

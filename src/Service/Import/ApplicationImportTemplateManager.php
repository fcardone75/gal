<?php


namespace App\Service\Import;


use App\Entity\ApplicationImportTemplate;
use App\Service\Contracts\Import\ApplicationImportTemplateManagerInterface;
use App\Service\Contracts\Import\Config\YamlFileLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ApplicationImportTemplateManager implements ApplicationImportTemplateManagerInterface
{
    use ImporterTrait;

    /** @var IOFactory */
    private $adapter;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var array */
    private $config;

    /**
     * ApplicationImportTemplateManager constructor.
     * @param YamlFileLoaderInterface $yamlFileLoader
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @throws Exception
     */
    public function __construct(
        YamlFileLoaderInterface $yamlFileLoader,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->adapter = IOFactory::createReader('Xlsx');
        $this->config = $yamlFileLoader->load('references.yaml');
        $this->logger = $logger;
    }

    /**
     * @param ApplicationImportTemplate $applicationImportTemplate
     * @return Spreadsheet
     * @throws \Exception
     */
    public function loadSpreadsheetForTemplate(ApplicationImportTemplate $applicationImportTemplate): Spreadsheet
    {
        if ($applicationImportTemplate->getSpreadsheet()) {
            return $applicationImportTemplate->getSpreadsheet();
        }
        if (!$applicationImportTemplate->getFilenameFile()) {
            throw new \Exception('The given ApplicationImportTemplate entity does not have any template file');
        }
        if (!$this->hasValidConfig()) {
            throw new \Exception('The configuration in app/config/import/references.yaml is not valid');
        }
        $file = $applicationImportTemplate->getFilenameFile();
        try {
            $lfs = new Filesystem();
            $tmpDir = isset($this->config['rootDir']) ? implode('/', [rtrim($this->config['rootDir'], '/'), 'var/tmp']) : '/tmp';
            $tmpFilename = implode('/', [rtrim($tmpDir, '/'), $file->getFilename()]);
            $lfs->dumpFile($tmpFilename, $file->getContent());
            $spreadsheet = $this->adapter->load($tmpFilename);
            $applicationImportTemplate
                ->setSpreadsheet($spreadsheet)
                ->setReferencesSheetName($this->config['sheet']['name'])
                ->setVersionCell($this->config['sheet']['revision_cell'])
                ->updateRevisionFromReferenceSheet();
            return $applicationImportTemplate->getSpreadsheet();
        } catch (\Exception $e) {
            $this->logger->error('Unable to load spreadsheet from file {filename} due to an exception: {exception}', [
                'filename' => $file->getFilename(),
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getReferencesSheetForTemplate(ApplicationImportTemplate $applicationImportTemplate): Worksheet
    {
        $spreadsheet = $applicationImportTemplate->getSpreadsheet() ?: $this->loadSpreadsheetForTemplate($applicationImportTemplate);
        $referencesSheet = $spreadsheet->getSheetByName($this->config['sheet']['name']);
        if (!$referencesSheet) {
            throw new \Exception(sprintf('The given file does not contain a "%s" worksheet', $this->config['sheet']['name']));
        }
        return $referencesSheet;
    }

    /**
     * {@inheritDoc}
     */
    public function updateReferencesFromTemplate(ApplicationImportTemplate $applicationImportTemplate)
    {
        $referencesSheet = $this->getReferencesSheetForTemplate($applicationImportTemplate);

        foreach ($this->config['entities'] as $className => $classConfig) {
            if (isset($classConfig['alias_of']) && $classConfig['alias_of']) {
                $className = $classConfig['alias_of'];
            }
            $this->importReferenceEntity($referencesSheet, $applicationImportTemplate, $className, $classConfig);
        }
    }

    /**
     * @param ApplicationImportTemplate $applicationImportTemplate
     * @param Worksheet $worksheet
     * @throws \Exception
     */
    protected function setVersionOnTemplate(ApplicationImportTemplate $applicationImportTemplate, Worksheet $worksheet)
    {
        $versionCell = $worksheet->getCell($this->config['sheet']['revision_cell']);

        if (!$versionCell || !preg_match('/^(\d+\.)?(\d+\.)?(\d+)$/', $versionCell->getValue())) {
            throw new \Exception(sprintf('The given file has an invalid version specified at cell "%2"', $this->config['sheet']['revision_cell']));
        }

        $applicationImportTemplate->setRevision($versionCell->getValue());
    }

    /**
     * @param Worksheet $worksheet
     * @param ApplicationImportTemplate $applicationImportTemplate
     * @param $className
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    protected function importReferenceEntity(
        Worksheet $worksheet,
        ApplicationImportTemplate $applicationImportTemplate,
        $className,
        $classConfig
    ) {
        $rowIterator = $worksheet->getRowIterator();
        $rowIterator->resetStart($classConfig['row_start']);

        foreach ($rowIterator as $row) {
            if ($this->isRowEmpty($row, $this->getMappedCellsForEntityConfig($classConfig['map']))) {
                break;
            }
            $skip = false;
            $entity = new $className();
            $entity->setTemplate($applicationImportTemplate);
            foreach ($classConfig['map'] as $property => $columnConfig) {
                $columnConfig = $this->normalizeColumnConfig($columnConfig);
                $column = $columnConfig['column'];
                try {
                    $cell = $worksheet->getCell($column . $row->getRowIndex());
                    $method = $this->setterForProperty($property);

                    $entity->{$method}($this->getCellValueUsingConfig($cell, $columnConfig));

                    $this->handleCallsForClassAndColumn($className, $columnConfig, $entity);
                } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                    $skip = true;
                    $this->logger->error('Unable to read cell {cell} from sheet {sheet}', [
                        'cell' => $column . $row->getRowIndex(),
                        'sheet' => $worksheet->getTitle()
                    ]);
                } catch (\TypeError | \Exception $e) {
                    $skip = true;
                    $this->logger->error('A reference entity could not be imported due to an exception/error. Error {error}', [
                        'error' => $e->getMessage()
                    ]);
                    break;
                }
            }
            if (!$skip) {
                $this->entityManager->persist($entity);
            }
        }
    }

    /**
     * @return bool
     */
    protected function hasValidConfig(): bool
    {
        if (!isset($this->config['sheet']) || !isset($this->config['sheet']['name']) || !$this->config['sheet']['name']) {
            $this->logger->error('"sheet", "sheet.name" or both nodes in references configuration are missing or "sheet.name" is empty');
            return false;
        }

        if (!isset($this->config['entities'])) {
            $this->logger->error('"entities" node in references configuration is missing or is empty');
            return false;
        }

        foreach ($this->config['entities'] as $entity => $config) {
            if (isset($config['alias_of']) && $config['alias_of']) {
                $entity = $config['alias_of'];
            }
            if (!class_exists($entity)) {
                $this->logger->error('Class "{class}" does not exist', ['class' => $entity]);
                return false;
            }
            if (!isset($config['row_start']) || !$config['row_start']) {
                $this->logger->error('"row_start" must be defined for class "{class}"', ['class' => $entity]);
                return false;
            }
            if (!isset($config['map']) || !$config['map'] || !is_array($config['map'])) {
                $this->logger->error('"map" node must be defined for class "{class}" and it must be an array', ['class' => $entity]);
                return false;
            }
            foreach ($config['map'] as $property => $columnConfig) {
                if (!method_exists($entity, $method = $this->setterForProperty($property))) {
                    $this->logger->error('Setter method {method} for property {property} does not exists in class {class}', [
                        'method' => $method,
                        'property' => $columnConfig,
                        'class' => $entity
                    ]);
                    return false;
                }
                if (is_array($columnConfig)) {
                    if (!isset($columnConfig['column'])) {
                        $this->logger->error('"column" must be defined for property "{property}" of class "{class}"', [
                            'property' => $property,
                            'class' => $entity
                        ]);
                        return false;
                    }
                    if (isset($columnConfig['calls']) && is_array($columnConfig['calls'])) {
                        foreach (array_keys($columnConfig['calls']) as $method) {
                            if (!method_exists($entity, $method)) {
                                $this->logger->error('Method {method} specified in "calls" does not exists in class {class}', [
                                    'method' => $method,
                                    'class' => $entity
                                ]);
                                return false;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    protected function handleCallsForClassAndColumn($className, $columnConfig, $entity)
    {
        if (is_array($columnConfig) && isset($columnConfig['calls']) && $calls = $columnConfig['calls']) {
            foreach ($calls as $call => $args) {
                if(method_exists($className, $call)) {
                    if (is_array($args)) {
                        call_user_func_array([$entity, $call], $args);
                    } else {
                        call_user_func([$entity, $call], $args);
                    }
                }
            }
        }
    }
}

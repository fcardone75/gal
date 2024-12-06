<?php

namespace App\Service\Import;

use App\Entity\AdditionalContribution;
use App\Entity\Application;
use App\Entity\ApplicationImport;
use App\Entity\ApplicationImportTemplate;
use App\Entity\AssuranceEnterpriseImport;
use App\Entity\FinancingImport;
use App\Entity\LeasingImport;
use App\Error\Import\ImportError;
use App\Service\Contracts\Import\ApplicationImportManagerInterface;
use App\Service\Contracts\Import\Config\YamlFileLoaderInterface;
use App\Service\Contracts\TypeConverterInterface;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApplicationImportManager implements ApplicationImportManagerInterface
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

    /** @var TypeConverterInterface */
    private $typeConverter;

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

    /**
     * ApplicationImporter constructor.
     * @param YamlFileLoaderInterface $yamlFileLoader
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param ValidatorInterface $validator
     * @param TypeConverterInterface $typeConverter
     * @param LoggerInterface $logger
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function __construct(
        YamlFileLoaderInterface $yamlFileLoader,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        TypeConverterInterface $typeConverter,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->validator = $validator;
        $this->typeConverter = $typeConverter;
        $this->logger = $logger;
        $this->adapter = IOFactory::createReader('Xlsx');
        $this->configFilePath = $yamlFileLoader->getLocator()->locate('application_import.yaml');
        $this->config = $yamlFileLoader->load('application_import.yaml');
        if (!$this->hasValidConfig()) {
            throw new \Exception(sprintf('The configuration in "%s" is not valid', $this->configFilePath));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function loadSpreadsheetForImport(ApplicationImport $applicationImport): Spreadsheet
    {
        if ($applicationImport->getSpreadsheet()) {
            return $applicationImport->getSpreadsheet();
        }
        if (!$applicationImport->getFilenameFile()) {
            throw new \Exception('The given ApplicationImport entity does not have any template file');
        }
        $file = $applicationImport->getFilenameFile();
        try {
            $lfs = new Filesystem();
            $tmpDir = isset($this->config['rootDir']) ? implode('/', [rtrim($this->config['rootDir'], '/'), 'var/tmp']) : '/tmp';
            $tmpFilename = implode('/', [rtrim($tmpDir, '/'), $file->getFilename()]);
            $lfs->dumpFile($tmpFilename, $file->getContent());
            $spreadsheet = $this->adapter->load($tmpFilename);
            $referencesSheet = $spreadsheet->getSheetByName($this->config['validation']['references_sheet_name']);
            $revisionCell = $referencesSheet->getCell($this->config['validation']['revision_cell']);
            $template = $this->entityManager->getRepository(ApplicationImportTemplate::class)->findOneBy([
                'revision' => $revisionCell->getFormattedValue()
            ]);
            $applicationImport
                ->setSpreadsheet($spreadsheet)
                ->setTemplate($template);
            return $applicationImport->getSpreadsheet();
        } catch (\Exception $e) {
            $this->logger->error('Unable to load spreadsheet from file {filename} due to an exception: {exception}', [
                'filename' => $file->getFilename(),
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createImport(ApplicationImport $applicationImport): array
    {
        $this->errors = [];
        try {
            $spreadsheet = $this->loadSpreadsheetForImport($applicationImport);
        } catch (\Exception | \TypeError $e) {
            $spreadsheet = null;
            $this->logger->error('Import failed due to an exception: {exception}', [
                'exception' => $e->getMessage()
            ]);
            $this->errors[] = ImportError::create()
                ->setContext(ImportError::CONTEXT_SPREADSHEET_FILE)
                ->setMessage($this->translator->trans('application_importer.errors.file.exception', [
                    'error' => $e->getMessage()
                ]));
        }

        if ($spreadsheet) {
            foreach ($this->config['sheets'] as $code => $sheetConfig) {
                if (isset($sheetConfig['import']) && !$sheetConfig['import']) {
                    continue;
                }
                $worksheet = $spreadsheet->getSheetByName($sheetConfig['name']);

                if (!$worksheet) {
                    $this->errors[] = ImportError::create()
                        ->setContext(ImportError::CONTEXT_SPREADSHEET_FILE)
                        ->setMessage($this->translator->trans('application_importer.errors.file.missing_sheet', [
                            'sheet' => $sheetConfig['name']
                        ]));
                    continue;
                }

                $rowIterator = $worksheet->getRowIterator();
                try {
                    $rowIterator->resetStart($sheetConfig['row_start']);
                } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                    $this->logger->error('Import of sheet "{sheet}" failed due to an exception: {exception}', [
                        'sheet' => $sheetConfig['name'],
                        'exception' => $e->getMessage()
                    ]);
                    $this->errors[] = ImportError::create()
                        ->setContext(ImportError::CONTEXT_SPREADSHEET_SHEET)
                        ->setSheet($sheetConfig['name'])
                        ->setMessage($this->translator->trans('application_importer.errors.file.reset_row_start_failed', [
                            'sheet' => $sheetConfig['name']
                        ]));
                    continue;
                }

                foreach ($rowIterator as $row) {
                    try {
                        if ($this->isRowEmpty($row, $this->getMappedCellsForEntityConfig($sheetConfig['map']))) {
                            break;
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Import of row "{row}" of sheet "{sheet}" failed due to an exception: {exception}', [
                            'row' => $row->getRowIndex(),
                            'sheet' => $sheetConfig['name'],
                            'exception' => $e->getMessage()
                        ]);
                        $this->errors[] = ImportError::create()
                            ->setContext(ImportError::CONTEXT_SPREADSHEET_ROW)
                            ->setSheet($sheetConfig['name'])
                            ->setRow($row->getRowIndex())
                            ->setError($e->getMessage())
                            ->setMessage($this->translator->trans('application_importer.errors.sheet.unable_to_read_row', [
                                'row' => $row->getRowIndex(),
                                'sheet' => $sheetConfig['name'],
                                'error' => $e->getMessage()
                            ]));
                        continue;
                    }
                    $entity = new $sheetConfig['entity']();
                    $entity->setRow($row->getRowIndex());
                    $this->addRowEntityToApplicationImport($applicationImport, $entity, $sheetConfig['entity']);
                    foreach ($sheetConfig['map'] as $property => $columnConfig) {
                        $columnConfig = $this->normalizeColumnConfig($columnConfig);
                        $column = $columnConfig['column'];
                        try {
                            $cell = $worksheet->getCell($column . $row->getRowIndex());
                        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                            $this->logger->error('Import of cell "{cell}" of sheet "{sheet}" failed due to an exception: {exception}', [
                                'cell' => $column . $row->getRowIndex(),
                                'sheet' => $sheetConfig['name'],
                                'exception' => $e->getMessage()
                            ]);
                            $this->errors[] = ImportError::create()
                                ->setContext(ImportError::CONTEXT_SPREADSHEET_ROW)
                                ->setSheet($sheetConfig['name'])
                                ->setRow($row->getRowIndex())
                                ->setCell($column . $row->getRowIndex())
                                ->setMessage($this->translator->trans('application_importer.errors.sheet.unable_to_get_cell', [
                                    'cell' => $column . $row->getRowIndex(),
                                    'sheet' => $sheetConfig['name']
                                ]));
                            continue;
                        }
                        $value = $this->getCellValueUsingConfig($cell, $columnConfig);
                        $value = $this->executePropertyCallbacks($columnConfig, $value);
                        $method = $this->setterForProperty($property);
                        try {
                            $entity->{$method}($value);
                        } catch (\TypeError | \Exception $e) {
                            $this->logger->error('Import of cell "{cell}" value of sheet "{sheet}" failed due to an exception: {exception}', [
                                'cell' => $column . $row->getRowIndex(),
                                'sheet' => $sheetConfig['name'],
                                'exception' => $e->getMessage()
                            ]);
                            $this->errors[] = ImportError::create()
                                ->setContext(ImportError::CONTEXT_SPREADSHEET_ROW)
                                ->setMessage($this->translator->trans('application_importer.errors.row.type_error', [
                                    'sheet' => $sheetConfig['name'],
                                    'cell' => $column . $row->getRowIndex(),
                                    'error' => $e->getMessage()
                                ]));
                            continue;
                        }
                    }

                    $this->entities[$code][] = $entity;
                    $this->entityManager->persist($entity);
                }
            }
            if (!$this->entities) {
                $this->logger->error('No valid entities to import');
                $this->errors[] = ImportError::create()
                    ->setContext(ImportError::CONTEXT_SPREADSHEET_FILE)
                    ->setMessage($this->translator->trans('application_importer.errors.file.no_valid_entities'));
            }
        }

        if ($this->errors) {
            $applicationImport
                ->setStatus(ApplicationImport::STATUS_FAILED)
                ->setErrors($this->convertImportErrorsToArray($this->errors));
        } else {
            $applicationImport->setStatus(ApplicationImport::STATUS_ACQUIRED);
        }

        return $this->errors;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ApplicationImport $applicationImport): array
    {
        $this->errors = [];
        $errors = $this->validator->validate($applicationImport);

        if ($errors->count() > 0) {
            $applicationImport->setStatus(ApplicationImport::STATUS_VALIDATION_FAILED);
            foreach ($errors as $error) {
                /** @var ConstraintViolation $error */
                if ($error->getPropertyPath()) {
                    if (preg_match('/(.*)\[(\d)\]\.(.*)/', $error->getPropertyPath(), $matches)) {
                        list($match, $sheetEntityCollection, $index, $property) = $matches;
                        $getter = 'get' . ucfirst($sheetEntityCollection);
                        $sheetEntity = $applicationImport->{$getter}()->get($index);
                        $class = get_class($sheetEntity);
                        $mapping = array_filter($this->config['sheets'], function ($item) use ($class) {
                            return isset($item['entity']) && $item['entity'] === $class;
                        });
                        if ($mapping) {
                            $mapping = array_shift($mapping);
                            if (isset($mapping['map']) && isset($mapping['map'][$property])) {
                                $column = is_array($mapping['map'][$property]) ? $mapping['map'][$property]['column'] : $mapping['map'][$property];
                                $cell = $column . $sheetEntity->getRow();
                                $this->errors[] = ImportError::create()
                                    ->setContext(ImportError::CONTEXT_SPREADSHEET_ROW)
                                    ->setSheet($mapping['name'])
                                    ->setRow($sheetEntity->getRow())
                                    ->setCell($cell)
                                    ->setMessage($this->translator->trans('application_importer.errors.validation', [
                                        'sheet' => $mapping['name'],
                                        'cell' => $cell,
                                        'error_message' => $error->getMessage()
                                    ]));
                            }
                        }
                    }
                }
            }
            $applicationImport
                ->setStatus(ApplicationImport::STATUS_VALIDATION_FAILED)
                ->setErrors($this->convertImportErrorsToArray($this->errors));
        } else {
            $applicationImport->setStatus(ApplicationImport::STATUS_VALIDATION_SUCCEEDED);
        }

        $this->entityManager->flush();

        return $this->errors;
    }

    /**
     * {@inheritDoc}
     */
    public function import(ApplicationImport $applicationImport): array
    {
        $conversionConfig = array_values($this->config['conversion']);
        $this->errors = [];
        $scheduledEntitiesToPersist = [];

        if ($applicationImport->getStatus() !== ApplicationImport::STATUS_VALIDATION_SUCCEEDED) {
            return $this->errors;
        }

        $firstEntityConfig = array_shift($conversionConfig);
        /** @var AssuranceEnterpriseImport[] $entitiesToConvert */
        $entitiesToConvert = $this->entityManager->getRepository($firstEntityConfig['entity'])->findBy([
            'applicationImport' => $applicationImport
        ]);

        foreach ($entitiesToConvert as $entityToConvert) {
            $entity = new Application();

            $entity->setApplicationImport($applicationImport);
            $entity->setConfidi($applicationImport->getConfidi());
            $this->copyFromSourceToTargetUsingConfig($entityToConvert, $entity, $firstEntityConfig['map']);
            if (isset($firstEntityConfig['callbacks'])) {
                $this->executeEntityCallbacks($entity, $firstEntityConfig['callbacks']);
            }

            $pairedEntity = null;
            foreach ($conversionConfig as $config) {
                $criteria = [];

                foreach ($config['criteria'] as $field) {
                    $valueGetter = $this->getterForProperty($field);
                    $criteria[$field] = $entityToConvert->{$valueGetter}();
                }

                $pairedEntity = $this->entityManager->getRepository($config['entity'])->findOneBy($criteria);

                if ($pairedEntity) {
                    $this->copyFromSourceToTargetUsingConfig($pairedEntity, $entity, $config['map']);
                    if (isset($config['callbacks'])) {
                        $this->executeEntityCallbacks($pairedEntity, $config['callbacks']);
                    }
                    break;
                }
            }

            if (!$pairedEntity) {
                $this->errors[] = [
                    'context' => ImportError::CONTEXT_IMPORT,
                    'contextId' => $entityToConvert->getPracticeId(),
                    'message' => $this->translator->trans('application_importer.errors.import.no_matched_entities', [
                        'practiceId' => $entityToConvert->getPracticeId()
                    ])
                ];
            } else {
                $scheduledEntitiesToPersist[$entity->getPracticeId()] = $entity;
            }
        }
        if ($this->errors) {
            $applicationImport->setErrors($this->errors);
        } else {
            foreach ($scheduledEntitiesToPersist as $entityToPersist) {
                $this->entityManager->persist($entityToPersist);
            }
            $applicationImport->setStatus(ApplicationImport::STATUS_IMPORTED);
            $this->entityManager->flush();
        }
        return $this->errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function hasValidConfig(): bool
    {
        if (!isset($this->config['validation']) ||
            !isset($this->config['validation']['references_sheet_name']) ||
            !isset($this->config['validation']['revision_cell'])) {
            $this->logger->error('The configuration file "{filepath}" is missing, totally or in part, the "validation" section. Required nodes are {required_nodes}', [
                'filepath' => $this->configFilePath,
                'required_nodes' => implode(',', [
                    '"validation"',
                    '"validation.references_sheet_name"',
                    '"validation.revision_cell"'
                ])
            ]);
            return false;
        }
        if (!isset($this->config['sheets']) || !$this->config['sheets'] || !is_array($this->config['sheets'])) {
            $this->logger->error('The configuration file "{filepath}" is missing the "sheets" configuration node, the node is empty or it is not an array', [
                'filepath' => $this->configFilePath
            ]);
            return false;
        }
        foreach ($this->config['sheets'] as $sheetConfig) {
            if (!isset($sheetConfig['name']) || !$sheetConfig['name']) {
                $this->logger->error('The configuration file "{filepath}" is missing "name" configuration', [
                    'filepath' => $this->configFilePath
                ]);
                return false;
            }
            if (!isset($sheetConfig['import']) || $sheetConfig['import']) {
                if (!isset($sheetConfig['entity']) || !$sheetConfig['entity']) {
                    $this->logger->error('The configuration file "{filepath} is missing "entity" configuration for sheet "{sheet}"', [
                        'filepath' => $this->configFilePath,
                        'sheet' => $sheetConfig['name']
                    ]);
                    return false;
                }
                if (!class_exists($sheetConfig['entity'])) {
                    $this->logger->error('Entity class "{class}" for sheet "{sheet}" does not exist', [
                        'class' => $sheetConfig['entity'],
                        'sheet' => $sheetConfig['name']
                    ]);
                    return false;
                }
                if (!isset($sheetConfig['row_start']) || !$sheetConfig['row_start']) {
                    $this->logger->error('The configuration file "{filepath} is missing "row_start" configuration for sheet "{sheet}"', [
                        'filepath' => $this->configFilePath,
                        'sheet' => $sheetConfig['name']
                    ]);
                    return false;
                }
                if (!isset($sheetConfig['map']) || !$sheetConfig['map'] || !is_array($sheetConfig['map'])) {
                    $this->logger->error('The configuration file "{filepath} is missing "map" configuration for sheet "{sheet}" or it is invalid', [
                        'filepath' => $this->configFilePath,
                        'sheet' => $sheetConfig['name']
                    ]);
                    return false;
                }
                foreach ($sheetConfig['map'] as $property => $columnConfig) {
                    if (is_string($columnConfig)) {
                        $columnConfig = ['column' => $columnConfig];
                    }
                    if (!method_exists($sheetConfig['entity'], $this->setterForProperty($property))) {
                        $this->logger->error('There is no method "{method}" for property "{property}" in class "{class}"', [
                            'method' => $this->setterForProperty($property),
                            'property' => $property,
                            'class' => $sheetConfig['entity']
                        ]);
                        return false;
                    }
                    if (!$columnConfig['column']) {
                        $this->logger->error('The mapping information for property "{property}" of class "{class}" for sheet "{sheet}" in configuration file "{filepath} is missing', [
                            'property' => $property,
                            'class' => $sheetConfig['entity'],
                            'sheet' => $sheetConfig['name'],
                            'filepath' => $this->configFilePath
                        ]);
                        return false;
                    }
                    if (isset($columnConfig['callbacks']) &&
                        !$this->validatePropertyCallbacks($columnConfig['callbacks'])) {
                        return false;
                    }
                }
            }
        }

        if (!isset($this->config['conversion']) || !$this->config['conversion'] || !is_array($this->config['conversion'])) {
            $this->logger->error('Configuration is missing the "conversion" node or it is invalid');
            return false;
        }

        $starterEntity = array_filter($this->config['conversion'], function ($config) {
            return isset($config['starter']) && $config['starter'];
        });

        if (!$starterEntity || count($starterEntity) > 1) {
            $this->logger->error('One, and only one entity can be configured as "starter" in conversion configuration. {count} was found', [
                'count' => count($starterEntity)
            ]);
            return false;
        } else {
            $starterEntity = array_shift($starterEntity);
            if (isset($starterEntity['criteria']) && $starterEntity['criteria']) {
                $this->logger->error('An entity configured to be the entity to start with for conversion cannot have also a matching criteria for pairing with other entities');
                return false;
            }
            if (!isset($starterEntity['entity']) || !$starterEntity['entity']) {
                $this->logger->error('An entity configured to be the entity to start with for conversion must provide "entity" configuration');
                return false;
            }
        }

        uasort($this->config['conversion'], function ($a) {
            return isset($a['starter']) ? -1 : 0;
        });

        foreach ($this->config['conversion'] as $code => $entityConversionConfig) {
            if (!isset($entityConversionConfig['entity']) || !is_string($entityConversionConfig['entity'])) {
                $this->logger->error('The conversion configuration with ID "{id}" is missing the entity node or it is invalid', [
                    'id' => $code
                ]);
                return false;
            }
            if (!class_exists($entityConversionConfig['entity'])) {
                $this->logger->error('The conversion configuration with ID "{id}" is declaring "{entity}" as entity but class does not exist', [
                    'id' => $code,
                    'entity' => $entityConversionConfig['entity'],
                ]);
                return false;
            }
            if (isset($entityConversionConfig['criteria']) && $entityConversionConfig['criteria'] && !is_array($entityConversionConfig['criteria'])) {
                $this->logger->error('{entity} entity configuration for "criteria" is not valid', [
                    'entity' => $entityConversionConfig['entity']
                ]);
                return false;
            }
            $criteria = [];
            if (isset($entityConversionConfig['criteria'])) {
                foreach ($entityConversionConfig['criteria'] as $targetField => $sourceField) {
                    if (is_numeric($targetField)) {
                        $targetField = $sourceField;
                    }
                    if (!property_exists($starterEntity['entity'], $targetField)) {
                        $this->logger->error('Entity "{entity}" does not have a "{property}" property', [
                            'entity' => $starterEntity['entity'],
                            'property' => $targetField
                        ]);
                        return false;
                    }
                    if (!property_exists($entityConversionConfig['entity'], $sourceField)) {
                        $this->logger->error('Entity "{entity}" does not have a "{property}" property', [
                            'entity' => $entityConversionConfig['entity'],
                            'property' => $sourceField
                        ]);
                        return false;
                    }
                    $criteria[$targetField] = $sourceField;
                }
                $this->config['conversion'][$code]['criteria'] = $criteria;
            }

            if (!isset($entityConversionConfig['map']) || !$entityConversionConfig['map'] || !is_array($entityConversionConfig['map'])) {
                $this->logger->error('Entity "{entity}" conversion configuration is missing the "map" node', [
                    'entity' => $entityConversionConfig['entity']
                ]);
                return false;
            }
            array_walk($entityConversionConfig['map'], function (&$value, $key){
                if (is_string($value)) {
                    $value = ['property' => $value];
                }
                return $value;
            });
            $this->config['conversion'][$code]['map'] = $entityConversionConfig['map'];
            foreach ($entityConversionConfig['map'] as $sourceProperty => $targetProperty) {
                if (!method_exists($entityConversionConfig['entity'], $this->getterForProperty($sourceProperty))) {
                    $this->logger->error('Entity "{entity}" does not implement a "{method}" method', [
                        'entity' => $entityConversionConfig['entity'],
                        'method' => $this->getterForProperty($sourceProperty)
                    ]);
                    return false;
                }
                if (!method_exists(Application::class, $this->setterForProperty($targetProperty['property']))) {
                    $this->logger->error('Entity "{entity}" does not implement a "{method}" method', [
                        'entity' => Application::class,
                        'method' => $this->setterForProperty($targetProperty)
                    ]);
                    return false;
                }
                if (isset($targetProperty['callbacks']) &&
                    !$this->validatePropertyCallbacks($targetProperty['callbacks'])) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function validatePropertyCallbacks($propertyCallbacks): bool
    {
        if (is_string($propertyCallbacks)) {
            $propertyCallbacks = [$propertyCallbacks];
        }
        if (is_array($propertyCallbacks)) {
            foreach ($propertyCallbacks as $callback) {
                if (is_string($callback)) {
                    $callback = [$callback];
                }
                $method = array_shift($callback);
                if (!method_exists($this, $method)) {
                    $this->logger->error('Class "{entity}" does not implement a "{method}" method set as callback', [
                        'entity' => self::class,
                        'method' => $method
                    ]);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param $content
     * @return string
     */
    protected function normalizeCellContent($content): string
    {
        return strtolower(str_replace(
            [' ', '/', '.', 'à', 'é', 'è', 'ì', 'ò', 'ù'],
            ['_', '', '', 'a', 'e', 'e', 'i', 'o', 'u'],
            $content
        ));
    }

    /**
     * @param ApplicationImport $applicationImport
     * @param AssuranceEnterpriseImport|FinancingImport|LeasingImport $rowEntity
     * @param $entityClass
     */
    protected function addRowEntityToApplicationImport(ApplicationImport $applicationImport, $rowEntity, $entityClass)
    {
        switch ($entityClass) {
            case AssuranceEnterpriseImport::class:
                $applicationImport->addAssuranceEnterpriseImport($rowEntity);
                break;
            case FinancingImport::class:
                $applicationImport->addFinancingImport($rowEntity);
                break;
            case LeasingImport::class:
                $applicationImport->addLeasingImport($rowEntity);
                break;
        }
    }

    protected function copyFromSourceToTargetUsingConfig($sourceEntity, $targetEntity, $config)
    {
        foreach ($config as $sourceProperty => $targetProperty) {
            $getter = $this->getterForProperty($sourceProperty);
            $setter = $this->setterForProperty($targetProperty['property']);
            $value = $sourceEntity->{$getter}();

            $value = $this->executePropertyCallbacks($targetProperty, $value);

            $targetEntity->{$setter}($value);
        }
    }

    protected function executeEntityCallbacks($entity, array $callbacks = [])
    {
        foreach ($callbacks as $callback) {
            if (method_exists($this, $callback)) {
                $this->{$callback}($entity);
            }
        }
    }

    protected function executePropertyCallbacks(array $propertyConfig, $value)
    {
        if (isset($propertyConfig['callbacks'])) {
            if (is_string($propertyConfig['callbacks'])) {
                $propertyConfig['callbacks'] = [$propertyConfig['callbacks']];
            }
            foreach ($propertyConfig['callbacks'] as $callback) {
                if (is_string($callback)) {
                    $callback = [ $callback ];
                }
                switch (count($callback)) {
                    case 2:
                        list ($method, $args) = $callback;
                        if (is_string($args)) {
                            $args = [$args];
                        }
                        array_unshift($args, $value);
                        break;
                    default:
                        $method = array_shift($callback);
                        $args = [$value];
                }
                $value = call_user_func_array([$this, $method], $args);
            }
        }
        return $value;
    }

    protected function forceFloatToString($value): string
    {
        return $this->typeConverter->forceFloatToString($value);
    }

    protected function truncateString($string, $length)
    {
        return substr($string, 0, $length);
    }

    public function stringToInt(string $string): ?int
    {
        return $this->typeConverter->stringToInt($string);
    }

    public function stringToFloat(string $string): ?float
    {
        return $this->typeConverter->stringToFloat($string);
    }

    public function stringToDateTime(string $datetime, $format)
    {
        return $this->typeConverter->stringToDateTime($datetime, $format);
    }

    public function createAdditionalContributionItems(Application $application)
    {
        if (strtoupper($application->getAeAcInterestsContributionRequest()) === 'S') {
            $additionalContribution = new AdditionalContribution();
            $additionalContribution
                ->setType(AdditionalContribution::TYPE_CON)
                ->setInImport(true)
                ->setPresentationDate(new \DateTime());
            $application->addAdditionalContribution($additionalContribution);
        }
        if (strtoupper($application->getAeAcLostFundContributionRequest()) === 'S') {
            $additionalContribution = new AdditionalContribution();
            $additionalContribution
                ->setType(AdditionalContribution::TYPE_CFP)
                ->setInImport(true)
                ->setPresentationDate(new \DateTime());
            $application->addAdditionalContribution($additionalContribution);
        }
        if (strtoupper($application->getAeAcCommissionsRebateRequest()) === 'S') {
            $additionalContribution = new AdditionalContribution();
            $additionalContribution
                ->setType(AdditionalContribution::TYPE_ABB)
                ->setInImport(true)
                ->setPresentationDate(new \DateTime());
            $application->addAdditionalContribution($additionalContribution);
        }
    }

    protected function convertImportErrorsToArray($errors = []): array
    {
        return array_map(function (ImportError $err) {
            return [
                'context' => $err->getContext(),
                'contextId' => $err->getContextId(),
                'file' => $err->getFile(),
                'sheet' => $err->getSheet(),
                'row' => $err->getRow(),
                'cell' => $err->getCell(),
                'message' => $err->getMessage()
            ];
        }, $errors);
    }
}

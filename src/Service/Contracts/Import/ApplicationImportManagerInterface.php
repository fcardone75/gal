<?php

namespace App\Service\Contracts\Import;

use App\Entity\ApplicationImport;
use App\Error\Import\ImportError;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\Validator\ConstraintViolationListInterface;

interface ApplicationImportManagerInterface
{
    /**
     * @param ApplicationImport $applicationImport
     * @return mixed
     * @throws \Exception
     */
    public function loadSpreadsheetForImport(ApplicationImport $applicationImport): Spreadsheet;

    /**
     * @param ApplicationImport $applicationImport
     * @return ImportError[]
     */
    public function createImport(ApplicationImport $applicationImport): array;

    /**
     * @param ApplicationImport $applicationImport
     * @return ImportError[]
     */
    public function validate(ApplicationImport $applicationImport): array;

    /**
     * @param ApplicationImport $applicationImport
     * @return ImportError[]
     */
    public function import(ApplicationImport $applicationImport): array;

    /**
     * @return array
     */
    public function getConfig(): array;

    /**
     * @return array
     */
    public function getErrors(): array;
}

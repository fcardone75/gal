<?php

namespace App\Service\Contracts\Export;

use App\Entity\ApplicationImport;
use App\Error\Import\ImportError;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

interface ApplicationExportManagerInterface
{
    /**
     * @param array $applications
     * @return mixed
     * @throws \Exception
     */
    public function createApplicationsCsv(array $applications): StreamedResponse;

    /**
     * @return array
     */
    public function getConfig(): array;

//    /**
//     * @return array
//     */
//    public function getErrors(): array;
}

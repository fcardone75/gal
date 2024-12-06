<?php


namespace App\Service\Contracts\Import;


use App\Entity\ApplicationImportTemplate;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

interface ApplicationImportTemplateManagerInterface
{
    /**
     * @param ApplicationImportTemplate $applicationImportTemplate
     * @return Spreadsheet
     * @throws \Exception
     * @throws Exception
     */
    public function loadSpreadsheetForTemplate(ApplicationImportTemplate $applicationImportTemplate): Spreadsheet;

    /**
     * @param ApplicationImportTemplate $applicationImportTemplate
     * @return Worksheet
     * @throws \Exception
     */
    public function getReferencesSheetForTemplate(ApplicationImportTemplate $applicationImportTemplate): Worksheet;

    /**
     * @param ApplicationImportTemplate $applicationImportTemplate
     * @return void
     * @throws \Exception
     * @throws Exception
     */
    public function updateReferencesFromTemplate(ApplicationImportTemplate $applicationImportTemplate);
}

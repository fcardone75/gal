<?php


namespace App\Validator\Constraints\Import;


class ValidImportSpreadsheet extends \Symfony\Component\Validator\Constraint
{
    public $errorPath;

    public $expectedSheetCount;

    public $invalidRevisionMessage = 'valid_import_spreadsheet.revision_invalid';

    public $sheetCountMismatchMessage = 'valid_import_spreadsheet.sheet_count_mismatch';

    public $missingSheetMessage = 'valid_import_spreadsheet.missing_sheet';

    public $noReferencesSheetMessage = 'valid_import_spreadsheet.no_references_sheet';

    public $noRevisionMessage = 'valid_import_spreadsheet.no_revision';

    public $revisionNotFoundMessage = 'valid_import_spreadsheet.revision_not_found';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

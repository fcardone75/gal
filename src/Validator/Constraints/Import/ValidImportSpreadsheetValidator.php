<?php


namespace App\Validator\Constraints\Import;


use App\Entity\ApplicationImport;
use App\Entity\ApplicationImportTemplate;
use App\Service\Contracts\Import\ApplicationImportManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidImportSpreadsheetValidator extends \Symfony\Component\Validator\ConstraintValidator
{
    /** @var ApplicationImportManagerInterface */
    private $applicationImportManager;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ApplicationImportManagerInterface $applicationImportManager,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->applicationImportManager = $applicationImportManager;
    }

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidImportSpreadsheet) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ValidImportSpreadsheet');
        }

        if (!$value instanceof ApplicationImport) {
            return;
        }

        try {
            $config = $this->applicationImportManager->getConfig();
            $spreadsheet = $this->applicationImportManager->loadSpreadsheetForImport($value);

            $referencesSheet = $spreadsheet->getSheetByName($config['validation']['references_sheet_name']);

            if (!$referencesSheet) {
                $this->context->buildViolation($constraint->noReferencesSheetMessage)
                    ->setParameter('references_sheet', $config['validation']['references_sheet_name'])
                    ->atPath($constraint->errorPath)
                    ->addViolation();
                return;
            }

            if ($spreadsheet->getSheetCount() !== count($config['sheets'])) {
                $this->context->buildViolation($constraint->sheetCountMismatchMessage)
                    ->setParameter('actual', $spreadsheet->getSheetCount())
                    ->setParameter('expected', count($config['sheets']))
                    ->atPath($constraint->errorPath)
                    ->addViolation();
                return;
            }

            foreach ($config['sheets'] as $sheetConfig) {
                if (!$spreadsheet->getSheetByName($sheetConfig['name'])) {
                    $this->context->buildViolation($constraint->missingSheetMessage)
                        ->setParameter('sheet_name', $sheetConfig['name'])
                        ->atPath($constraint->errorPath)
                        ->addViolation();
                    return;
                }
            }

            $revision = null;
            $template = null;
            if ($revisionNumberCell = $referencesSheet->getCell($config['validation']['revision_cell'])) {
                $revision = $revisionNumberCell->getValue();

                $template = $this->entityManager->getRepository(ApplicationImportTemplate::class)->findOneBy([
                    'revision' => $revision,
                    'active' => true
                ]);
            }
            if (!$revision) {
                $this->context->buildViolation($constraint->noRevisionMessage)
                    ->setParameter('revision_cell', $config['validation']['revision_cell'])
                    ->setParameter('references_sheet', $config['validation']['references_sheet_name'])
                    ->atPath($constraint->errorPath)
                    ->addViolation();
                return;
            }
            if (!$template) {
                $this->context->buildViolation($constraint->revisionNotFoundMessage)
                    ->setParameter('revision_number', $revision)
                    ->setParameter('references_sheet', $config['validation']['references_sheet_name'])
                    ->setParameter('revision_cell', $config['validation']['revision_cell'])
                    ->atPath($constraint->errorPath)
                    ->addViolation();
                return;
            }

        } catch (\Exception $e) {
            throw new \LogicException('Unable to validate import spreadsheet due to an exception: ' . $e->getMessage());
        }
    }
}

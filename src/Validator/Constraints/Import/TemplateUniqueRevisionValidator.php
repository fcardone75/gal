<?php


namespace App\Validator\Constraints\Import;


use App\Entity\ApplicationImportTemplate;
use App\Service\Contracts\Import\ApplicationImportTemplateManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TemplateUniqueRevisionValidator extends \Symfony\Component\Validator\ConstraintValidator
{
    /** @var ApplicationImportTemplateManagerInterface */
    private $applicationImportTemplateManager;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * TemplateUniqueRevisionValidator constructor.
     * @param ApplicationImportTemplateManagerInterface $applicationImportTemplateManager
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ApplicationImportTemplateManagerInterface $applicationImportTemplateManager,
        EntityManagerInterface $entityManager
    ) {
        $this->applicationImportTemplateManager = $applicationImportTemplateManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof TemplateUniqueRevision) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\TemplateUniqueRevision');
        }

        if (!$value instanceof ApplicationImportTemplate) {
            return;
        }

        try {
            $this->applicationImportTemplateManager->loadSpreadsheetForTemplate($value);
            $existing = $this->entityManager->getRepository(ApplicationImportTemplate::class)
                ->findOneBy([
                    'revision' => $value->getRevision()
                ]);
            if ($existing) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($constraint->errorPath)
                    ->setParameters([
                        'revision_number' => $value->getRevision(),
                        'revision_cell' => $value->getVersionCell()
                    ])
                    ->addViolation();
            }
        } catch (\Exception $e) {
            // TODO: raise custom exception
            return;
        }
    }
}

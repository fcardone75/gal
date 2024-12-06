<?php


namespace App\Validator\Constraints\Import;


use App\Entity\Application;
use App\Entity\AssuranceEnterpriseImport;
use App\Entity\FinancingImport;
use App\Entity\LeasingImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AvailablePracticeIdValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof AvailablePracticeId) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\AvailablePracticeId');
        }

        if (!($value instanceof AssuranceEnterpriseImport) && !($value instanceof FinancingImport) && !($value instanceof LeasingImport)) {
            return;
        }

        $existing = $this->entityManager->getRepository(Application::class)->findOneBy([
            'practiceId' => $value->getPracticeId(),
            'confidi' => $value->getApplicationImport()->getConfidi()
        ]);

        if ($existing) {
            $this->context->buildViolation($constraint->message)
                ->atPath('practiceId')
                ->setParameter('practiceId', $value->getPracticeId())
                ->addViolation();
        }
    }
}

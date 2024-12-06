<?php


namespace App\Validator\Constraints\Import;


use App\Entity\AssuranceEnterpriseImport;
use App\Entity\FinancingImport;
use App\Entity\LeasingImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ConsistentPracticeIdValidator extends ConstraintValidator
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

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ConsistentPracticeId) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\ConsistentPracticeId');
        }

        if (!$value instanceof AssuranceEnterpriseImport &&
            !$value instanceof FinancingImport &&
            !$value instanceof LeasingImport) {
            $supportedClasses = [
                AssuranceEnterpriseImport::class,
                FinancingImport::class,
                LeasingImport::class
            ];
            $last = array_pop($supportedClasses);
            $supportedClasses = implode(' and ', [implode(', ', $supportedClasses), $last]);
            throw new \LogicException(ConsistentPracticeId::class . ' validator can be applied only on instances of ' . $supportedClasses . ' classes');
        }

        switch (true) {
            case $value instanceof AssuranceEnterpriseImport:
                $checkEntities = [ FinancingImport::class => null, LeasingImport::class => null ];
                break;
            case $value instanceof FinancingImport:
            case $value instanceof LeasingImport:
                $checkEntities = [ AssuranceEnterpriseImport::class => null ];
                break;
            default:
                $checkEntities = [];
        }

        foreach ($checkEntities as $checkEntity => $entityValue) {
            $checkEntities[$checkEntity] = $this->entityManager->getRepository($checkEntity)->count([
                'applicationImport' => $value->getApplicationImport(),
                'practiceId' => $value->getPracticeId()
            ]);
        }

        $count = array_sum($checkEntities);
        if ($count > 1 || $count === 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('practiceId')
                ->setParameter('practice_id', $value->getPracticeId())
                ->setParameter('count', $count)
                ->addViolation();
        }

    }
}

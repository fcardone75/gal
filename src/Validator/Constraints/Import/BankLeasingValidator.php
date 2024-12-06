<?php


namespace App\Validator\Constraints\Import;


use App\Entity\AssuranceEnterpriseImport;
use App\Entity\FinancingImport;
use App\Entity\LeasingImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BankLeasingValidator extends ConstraintValidator
{
    use GetterMethod;

    /** @var EntityManagerInterface  */
    private $entityManager;

    /**
     * BankLeasingValidator constructor.
     * @param EntityManagerInterface $entityManager
     */
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
        // check that this validator is applied only on its relevant Constraint
        if (!$constraint instanceof BankLeasing) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\BankLeasing');
        }

        // check that value passed for validation is an instance of the relevant classes
        if (
            !($value instanceof AssuranceEnterpriseImport) &&
            !($value instanceof FinancingImport) &&
            !($value instanceof LeasingImport)
        ) {
            return;
        }

        foreach ($constraint->properties as $property) {
            $getter = $this->getterMethodForProperty($property);

            $propertyValue = $value->{$getter}();

            $existing = $this->entityManager->getRepository(\App\Entity\BankLeasing::class)->findOneBy([
                'name' => $propertyValue,
                'template' => $value->getApplicationImport()->getTemplate()
            ]);

            if (!$existing) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($property)
                    ->addViolation();
            }
        }
    }
}

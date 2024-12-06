<?php


namespace App\Validator\Constraints\Import;


use App\Entity\AssuranceEnterpriseImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class OfficeWorkplaceCityInRegionValidator extends \Symfony\Component\Validator\ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof OfficeWorkplaceCityInRegion) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\OfficeWorkplaceCityInRegion');
        }

        if (!$value instanceof AssuranceEnterpriseImport) {
            return;
        }

        if (!$value->getWorkplaceCity()) {
            return;
        }

        $existing = $this->entityManager->getRepository(\App\Entity\City::class)->findOneBy([
            'name' => $value->getWorkplaceCity(),
            'template' => $value->getApplicationImport()->getTemplate(),
            'inRegion' => true
        ]);

        if (!$existing) {
            $this->context->buildViolation($constraint->message)
                ->atPath('workplaceCity')
                ->addViolation();
        }
    }
}

<?php


namespace App\Validator\Constraints\Import;


use App\Entity\AssuranceEnterpriseImport;
use App\Service\Contracts\TypeConverterInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidAssuranceAmountValidator extends \Symfony\Component\Validator\ConstraintValidator
{
    /**
     * @var TypeConverterInterface
     */
    private $typeConverter;

    public function __construct(
        TypeConverterInterface $typeConverter
    ) {
        $this->typeConverter = $typeConverter;
    }

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidAssuranceAmount) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ValidAssuranceAmount');
        }

        if (!$value instanceof AssuranceEnterpriseImport) {
            return;
        }

        if (($financingImport = $value->getFinancingImport()) &&
            $financingImport->getDfLoanProvidedAtImport() === 'S' &&
            $this->typeConverter->stringToFloat($financingImport->getDfAmount()) <
            $this->typeConverter->stringToFloat($value->getGAssuranceAmount())) {
            $this->context->buildViolation($constraint->message)
                ->atPath('gAssuranceAmount')
                ->addViolation();
        }

        if (($leasingImport = $value->getLeasingImport()) &&
            $this->typeConverter->stringToFloat($leasingImport->getDclAmount()) <
            $this->typeConverter->stringToFloat($value->getGAssuranceAmount())) {
            $this->context->buildViolation($constraint->message)
                ->atPath('gAssuranceAmount')
                ->addViolation();
        }
    }
}

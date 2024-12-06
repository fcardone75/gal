<?php

namespace App\Validator\Constraints;

use App\Service\Contracts\FiscalCodeValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FiscalIdValidator extends ConstraintValidator
{
    /**
     * @var FiscalCodeValidatorInterface
     */
    private $fiscalCodeValidator;

    /**
     * FiscalCodeValidator constructor.
     * @param FiscalCodeValidatorInterface $fiscalCodeValidator
     */
    public function __construct(FiscalCodeValidatorInterface $fiscalCodeValidator)
    {
        $this->fiscalCodeValidator = $fiscalCodeValidator;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof FiscalId) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\FiscalCode');
        }

        if (!is_string($value)) {
            return;
        }

        if (preg_match($constraint->fiscalCodePattern, $value) && !$this->fiscalCodeValidator->validateCode($value)) {
            $violation = $this->context->buildViolation($constraint->message);
            if ($constraint->path) {
                $violation->atPath($constraint->path);
            }
            $violation->addViolation();
        }
    }
}

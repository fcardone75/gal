<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FloatStringLessThanOrEqualToValidator extends \Symfony\Component\Validator\ConstraintValidator
{

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof FloatStringLessThanOrEqualTo) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\FloatStringLessThanOrEqualTo');
        }

        if (null === $value || $value === '') {
            return;
        }

        if (null === $constraint->value) {
            return;
        }

        $floatValue = preg_replace('/(?<=\d)\.(?=\d)/', '', $value);
        $floatValue = preg_replace('/(?<=\d),(?=\d)/', '.', $floatValue);
        $floatValue = (float) $floatValue;

        if ($floatValue > (float) $constraint->value) {
            $this->context->addViolation($constraint->message);
        }
    }
}

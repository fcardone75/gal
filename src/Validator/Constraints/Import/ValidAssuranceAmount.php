<?php


namespace App\Validator\Constraints\Import;


class ValidAssuranceAmount extends \Symfony\Component\Validator\Constraint
{
    public $message = 'valid_assurance_amount.invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

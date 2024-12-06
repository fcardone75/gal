<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class BankLeasing extends Constraint
{
    /** @var array  */
    public $properties = [];

    public $message = 'bank_leasing_invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

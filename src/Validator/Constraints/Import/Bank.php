<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class Bank extends Constraint
{
    /** @var array  */
    public $properties = [];

    public $message = 'bank_invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class Country extends Constraint
{
    /** @var array  */
    public $properties = [];

    public $message = 'country_invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

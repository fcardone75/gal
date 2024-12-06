<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class City extends Constraint
{
    /** @var array  */
    public $properties = [];

    public $message = 'city_invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

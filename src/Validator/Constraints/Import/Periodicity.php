<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class Periodicity extends Constraint
{
    /** @var array  */
    public $properties = [];

    public $message = 'periodicity_invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

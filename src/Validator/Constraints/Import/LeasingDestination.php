<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class LeasingDestination extends Constraint
{
    /** @var array  */
    public $properties = [];

    public $message = 'leasing_destination_invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

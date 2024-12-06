<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class FinancialDestination extends Constraint
{
    /** @var array  */
    public $properties = [];

    public $message = 'financial_destination_invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class CompanySize extends Constraint
{
    /** @var array  */
    public $properties = [];

    public $message = 'company_size_invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

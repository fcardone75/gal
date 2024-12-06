<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class AtecoCode extends Constraint
{
    public $message = 'ateco_code_invalid';

    public $withDots = false;

    public function getTargets()
    {
        return Constraint::PROPERTY_CONSTRAINT;
    }
}

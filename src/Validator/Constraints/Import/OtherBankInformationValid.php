<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class OtherBankInformationValid extends \Symfony\Component\Validator\Constraint
{
    public $keyword = 'altro';

    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}

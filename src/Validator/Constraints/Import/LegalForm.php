<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class LegalForm extends Constraint
{
    /** @var array  */
    public $properties = [];

    public $message = 'legal_form_invalid';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

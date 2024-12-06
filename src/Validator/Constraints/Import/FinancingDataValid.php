<?php

namespace App\Validator\Constraints\Import;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FinancingDataValid extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The value "{{ value }}" is not valid.';

    public $constraints;

    public $classConstraints;

    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }


}

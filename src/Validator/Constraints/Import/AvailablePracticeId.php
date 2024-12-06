<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class AvailablePracticeId extends \Symfony\Component\Validator\Constraint
{
    public $message = 'available_practice_id_invalid';

    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}

<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class ConsistentPracticeId extends Constraint
{
    public $message = 'consistent_practice_id';

    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}

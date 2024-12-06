<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class AcApplicationMembersValid extends \Symfony\Component\Validator\Constraint
{
    public $message = 'assurance_enterprise_import.ac_application_members_valid';

    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}

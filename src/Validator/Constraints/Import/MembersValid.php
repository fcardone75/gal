<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class MembersValid extends Constraint
{
    public $messages = [
        'lastname' => 'assurance_enterprise_import.members_valid.lastname',
        'firstname' => 'assurance_enterprise_import.members_valid.firstname',
        'birth_date' => 'assurance_enterprise_import.members_valid.birth_date',
        'gender' => 'assurance_enterprise_import.members_valid.gender',
        'gender_invalid' => 'assurance_enterprise_import.members_valid.gender_invalid',
        'fiscal_code_empty' => 'assurance_enterprise_import.members_valid.fiscal_code_empty',
        'fiscal_code_invalid' => 'assurance_enterprise_import.members_valid.fiscal_code_invalid',
        'birth_place_conflict' => 'birth_place_conflict'
    ];

    public $constraints;

    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}

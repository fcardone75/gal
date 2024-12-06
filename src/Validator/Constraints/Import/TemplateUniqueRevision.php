<?php


namespace App\Validator\Constraints\Import;


use Symfony\Component\Validator\Constraint;

class TemplateUniqueRevision extends \Symfony\Component\Validator\Constraint
{
    public $message = 'template_unique_revision';

    public $errorPath;

    public function getTargets()
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}

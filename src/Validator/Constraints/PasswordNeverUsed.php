<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class PasswordNeverUsed extends Constraint
{
    public $message = 'password_already_used';

    public $lookBackItems;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return PasswordNeverUsedValidator::class;
    }
}

<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class UserPassword extends Constraint
{
    public $message = 'user_password';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return UserPasswordValidator::class;
    }
}

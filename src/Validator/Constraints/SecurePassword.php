<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class SecurePassword extends Constraint
{
    public $message = 'secure_password';

    public $chunkSize = 3;

    public $properties = [];

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return SecurePasswordValidator::class;
    }
}

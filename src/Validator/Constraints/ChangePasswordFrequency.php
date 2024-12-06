<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
class ChangePasswordFrequency extends Constraint
{
    public $message = 'change_password_frequency';

    public $errorPath = null;

    public $maxFrequency = 86400;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ChangePasswordFrequencyValidator::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

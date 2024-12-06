<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class FileP7M extends Constraint
{
    public $messageNotSigned = 'file_p7m_not_signed';
    public $messageMimeType = 'file_p7m_mime_type';

    public $allowSimplePdf = false;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return FileP7MValidator::class;
    }
}

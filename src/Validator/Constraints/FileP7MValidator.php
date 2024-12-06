<?php

namespace App\Validator\Constraints;

use App\Service\Contracts\DigitalSignatureReaderInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FileP7MValidator extends ConstraintValidator
{
    /**
     * @var DigitalSignatureReaderInterface
     */
    private $digitalSignatureReader;

    public function __construct(
        DigitalSignatureReaderInterface $digitalSignatureReader
    ) {
        $this->digitalSignatureReader = $digitalSignatureReader;
    }

    /**
     * @param UploadedFile $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof FileP7M) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ApplicationGroupFileP7M');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof File) {
            return;
        }

        if ($value->getError()) {
            $this->context->addViolation($value->getErrorMessage());
            return;
        }

        $isSigned = $this->digitalSignatureReader->isFileDigitalSigned($value);
        if(!$isSigned){
            if(!$constraint->allowSimplePdf) {
                // VIOLATION: pdf not allowed and file not digitally signed
                $this->context->addViolation($constraint->messageNotSigned);
            } elseif ($value->getClientMimeType() !== 'application/pdf'){
                // VIOLATION: pdf allowed but mimetipe not equal to application/pdf
                $this->context->addViolation($constraint->messageMimeType);
            }
            // VALIDATED: pdf allowed and mimetype equal to application/pdf
        }
        // VALIDATED: file correctly signed
    }
}

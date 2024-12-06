<?php


namespace App\Service\Contracts;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface DigitalSignatureReaderInterface
{
    public function isFileDigitalSigned(UploadedFile $file): bool;
}

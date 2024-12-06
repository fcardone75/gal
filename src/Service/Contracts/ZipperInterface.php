<?php


namespace App\Service\Contracts;

use Gaufrette\FilesystemInterface;
use Symfony\Component\HttpFoundation\Response;

interface ZipperInterface
{
    public function getResponseFromZipFiles($filesMap, $zipName = 'Documents.zip'): Response;
}

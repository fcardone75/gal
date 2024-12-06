<?php


namespace App\Service;


use App\Service\Contracts\ZipperInterface;
use Gaufrette\FilesystemInterface;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class Zipper implements ZipperInterface
{
    public function getResponseFromZipFiles($filesMap, $zipName = 'Documents.zip'): Response
    {
        $zip = new ZipArchive();
        $zip->open($zipName,ZipArchive::CREATE);

        foreach ($filesMap as $fileMap) {
            /** @var FilesystemInterface $fileSystem */
            $fileSystem = $fileMap['fileSystem'];
            $fileKey = $fileMap['fileKey'];
            $zip->addFromString(basename($fileKey), $fileSystem->read($fileKey));
        }

        $zip->close();

        $response = new Response(file_get_contents($zipName));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
        $response->headers->set('Content-length', filesize($zipName));

        @unlink($zipName);

        return $response;
    }
}

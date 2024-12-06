<?php

namespace App\Service;

use App\Service\Contracts\DigitalSignatureReaderInterface;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DigitalSignatureReader implements DigitalSignatureReaderInterface
{
    private $filesystemMap;

    public function __construct(FilesystemMap $filesystemMap)
    {
        $this->filesystemMap = $filesystemMap;
    }

    public function isFileDigitalSigned(UploadedFile $file): bool
    {
        $fileContent = file_get_contents($file->getPathname());
        $checksum = hash_file('sha256', $file->getPathname());

        $tmpFilePath = '../var/tmp/' . $checksum;
        if (!file_exists('../var/tmp/')) {
            mkdir('../var/tmp/', 0777, true);
        }
        $tmpFile = fopen($tmpFilePath, 'w');
        fwrite($tmpFile, $fileContent);
        fclose($tmpFile);

        // check if p7m is base64
		$stream_opts = [
			"ssl" => [
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			]
		];
		$file_is_base64 = $this->checkFileBase64($tmpFilePath, $stream_opts);

		if ($file_is_base64) {
			exec('cat ' . $tmpFilePath . ' | tr -d \'\r\n\' | openssl base64 -d -A | openssl pkcs7 -inform DER -print_certs', $out);
		} else {
			exec('openssl pkcs7 -inform DER -in ' . $tmpFilePath . ' -print_certs', $out);
		}
        //		exec('openssl pkcs7 -inform DER -in ' . $tmpFilePath . ' -print_certs', $out);

        unlink($tmpFilePath);

        if ($out) {
            return true;
        }

        return false;
    }

    private function checkFileBase64($filePath, $stream_opts): bool
    {
        // check if p7m is base64
		$file_is_base64 = false;

        // This is our Base64 string we want to check
		$input_check = file_get_contents($filePath, false, stream_context_create($stream_opts));
        // To support multiline values, fix input using str_replace(["\r", "\n"], '', $input).
		$input_check = str_replace(["\r", "\n"], '', $input_check);

        // By default PHP will ignore “bad” characters, so we need to enable the “$strict” mode
		$str = base64_decode($input_check, true);

        // If $input cannot be decoded the $str will be a Boolean “FALSE”
		if ($str !== false) {
			// Even if $str is not FALSE, this does not mean that the input is valid
			// This is why now we should encode the decoded string and check it against input
			$b64 = base64_encode($str);

			// Finally, check if input string and real Base64 are identical
			// Since “pad char” is optional use rtrim($input, '=') === rtrim($b64, '=') to check whether they are identical.
//			if ($input === $b64) {
			if (rtrim($input_check, '=') === rtrim($b64, '=')) {
				$file_is_base64 = true;
			}
		}

		return $file_is_base64;
	}
}

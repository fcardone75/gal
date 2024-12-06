<?php


namespace App\Service\Contracts;


interface FiscalCodeValidatorInterface
{
    const CODE_PATTERN = '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]$/i';

    const CHAR_FEMALE = 'F';

    const CHAR_MALE = 'M';

    public function validateCode(string $code): bool;
}

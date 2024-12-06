<?php


namespace App\Service\Contracts;


interface TypeConverterInterface
{
    public function stringToInt(string $string): ?int;

    public function stringToFloat(string $string): ?float;

    public function forceFloatToString($value): string;

    public function stringToDateTime(string $datetime, string $format);
}

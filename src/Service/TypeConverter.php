<?php


namespace App\Service;


use App\Service\Contracts\TypeConverterInterface;

class TypeConverter implements TypeConverterInterface
{

    public function stringToInt(string $string): ?int
    {
        return $string ? (int) $string : null;
    }

    public function stringToFloat(string $string): ?float
    {
        if ($string) {
            // remove thousands separators
            $string = preg_replace('/(?<=\d)\.(?=\d+)(?!\d+$)/', '', $string);
            // switch comma to period
            $string = preg_replace('/(?<=\d),(?=\d)/', '.', $string);
            return (float) $string;
        }
        return null;
    }

    public function forceFloatToString($value): string
    {
        if (is_numeric($value)) {
            return number_format((float) $value, 2, ',', '');
        }
        if (is_string($value)) {
            $parts = explode('.', $value);
            if (count($parts) <= 2) {
                return implode(',', $parts);
            } else {
                $decimals = array_pop($parts);
                return implode(',', [ implode($parts), $decimals ]);
            }
        }
        return (string) $value;
    }

    public function stringToDateTime(string $datetime, string $format)
    {
        if ($datetime) {
            return \DateTime::createFromFormat($format, $datetime) ?? null;
        }
        return null;
    }
}

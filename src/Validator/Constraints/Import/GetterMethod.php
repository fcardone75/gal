<?php


namespace App\Validator\Constraints\Import;


trait GetterMethod
{
    protected function getterMethodForProperty(string $property): string
    {
        return implode('', ['get', ucfirst($property)]);
    }
}

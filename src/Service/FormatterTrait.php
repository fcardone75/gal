<?php


namespace App\Service;


//use PhpOffice\PhpSpreadsheet\Cell\Cell;
//use PhpOffice\PhpSpreadsheet\Worksheet\Row;

trait FormatterTrait
{
//    protected $substitutionMap = [];

    protected function getterForProperty($property): string
    {
        if (is_array($property)) {
            $property = $property['property'];
        }
        return implode(['get', ucfirst($property)]);
    }

//    protected function setterForProperty($property): string
//    {
//        if (is_array($property)) {
//            $property = $property['property'];
//        }
//        return implode(['set', ucfirst($property)]);
//    }


    public function getImportFormatted($property): ?string
    {
        $getter = $this->getterForProperty($property);

        $value = $this->{$getter}();
        if ($value) {
            $value = '€ ' . number_format($value, 2, ',', '.');
        } else {
            $value = '--';
        }
        return $value;
    }

}

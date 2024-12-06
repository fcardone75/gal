<?php


namespace App\Validator\Constraints\Import;


class OfficeWorkplaceCityInRegion extends \Symfony\Component\Validator\Constraint
{
    public $message = 'office_workplace_city_in_region.workplace_city_not_in_region';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

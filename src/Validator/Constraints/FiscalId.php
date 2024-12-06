<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class FiscalId extends Constraint
{
    public $message = 'fiscal_code.deep_check_invalid';

    public $fiscalCodePattern = '/^([A-Za-z]{6}\d{2}[A-Za-z]{1}\d{2}[A-Za-z]{1}\d{3}[A-Za-z]{1})$/';

    public $pivaPattern = '/^(\d{11})$/';

    public $path = null;


    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }


}

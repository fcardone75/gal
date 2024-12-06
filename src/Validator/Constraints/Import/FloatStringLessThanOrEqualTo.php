<?php


namespace App\Validator\Constraints\Import;


class FloatStringLessThanOrEqualTo extends \Symfony\Component\Validator\Constraint
{
    public $message = 'float_string_less_than_or_equal';

    public $value;
}

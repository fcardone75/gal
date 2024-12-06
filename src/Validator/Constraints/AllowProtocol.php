<?php


namespace App\Validator\Constraints;


class AllowProtocol extends \Symfony\Component\Validator\Constraint
{
    public $message = 'allow_protocol_not_allowed';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

<?php

namespace App\Validator\Constraints;

use App\Model\UserInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SecurePasswordValidator extends ConstraintValidator
{

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof SecurePassword) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\SecurePassword');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$constraint->properties) {
            return;
        }

        $user = $this->context->getObject();

        if (!$user instanceof UserInterface) {
            throw new ConstraintDefinitionException('The User object must implement the UserInterface interface.');
        }

        if ($constraint->chunkSize) {
            $propValuesToCheck = $this->collectPropertiesValuesToCheck($constraint, $user);
            for ($i = 0; $i <= (strlen($value) - $constraint->chunkSize); $i++) {
                $checkStr = substr($value, $i, $constraint->chunkSize);
                foreach ($propValuesToCheck as $propValueToCheck) {
                    if (!is_array($propValueToCheck) && is_scalar($propValueToCheck)) {
                        $propValueToCheck = [$propValueToCheck];
                    }
                    if (is_array($propValueToCheck)) {
                        foreach ($propValueToCheck as $checkAgainstValue) {
                            if (is_scalar($checkAgainstValue) && stripos($checkAgainstValue, $checkStr) !== false) {
                                $this->context->addViolation($constraint->message);
                                return;
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getterForProperty($property)
    {
        $camelCaseProperty = preg_replace_callback(
            '/_([^_])/',
            function (array $m) {
                return ucfirst($m[1]);
            },
            $property
        );
        return implode('', ['get', ucfirst($camelCaseProperty)]);
    }

    protected function collectPropertiesValuesToCheck(SecurePassword $constraint, $object)
    {
        $propValues = [];

        if ($constraint->properties) {
            foreach ($constraint->properties as $property) {
                if (method_exists($object, $getterMethod = $this->getterForProperty($property))) {
                    $propValues[] = $object->{$getterMethod}();
                }
            }
        }

        return $propValues;
    }
}

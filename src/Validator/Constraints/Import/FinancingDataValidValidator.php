<?php

namespace App\Validator\Constraints\Import;

use App\Entity\FinancingImport;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FinancingDataValidValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\Constraints\Import\FinancingDataValid */

        if ( !($value instanceof FinancingImport)) {
            return;
        }

        if ($value->getDfLoanProvidedAtImport()==='S'){
            foreach ($constraint->classConstraints as $classConstraint){
                $this->context->getValidator()->validate($value,$classConstraint);
            }

            foreach ($constraint->constraints as $property => $constraintsConfig){
                $method = 'get'. ucfirst($property);
                $propertyValue = $value->{$method}();
                $errors = $this->context->getValidator()->validate($propertyValue, $constraintsConfig);
                foreach ($errors as $error) {
                    $this->context->buildViolation($error->getMessage())
                        ->atPath($property)
                        ->addViolation();
                }
            }
        }

    }
}

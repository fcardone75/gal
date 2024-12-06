<?php


namespace App\Validator\Constraints\Import;


use App\Entity\AssuranceEnterpriseImport;
use App\Entity\FinancingImport;
use App\Entity\LeasingImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MembersValidValidator extends ConstraintValidator
{
    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof MembersValid) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\MembersValid');
        }

        if (!$value instanceof AssuranceEnterpriseImport) {
            throw new \LogicException(ConsistentPracticeId::class . ' validator can be applied only on instances of ' . AssuranceEnterpriseImport::class . ' class');
        }

        $this->validateOwner($value, $constraint);
        $this->validateFirstMember($value, $constraint);
        $this->validateSecondMember($value, $constraint);
        $this->validateThirdMember($value, $constraint);
        $this->validateFourthMember($value, $constraint);

    }

    protected function validateOwner(AssuranceEnterpriseImport $assuranceEnterpriseImport, MembersValid $constraint)
    {
        $this->validateMember($assuranceEnterpriseImport, 'owner', $constraint);
    }

    protected function validateFirstMember(AssuranceEnterpriseImport $assuranceEnterpriseImport, MembersValid $constraint)
    {
        if (!$this->memberIsEmpty($assuranceEnterpriseImport, 'firstMember')) {
            $this->validateMember($assuranceEnterpriseImport, 'firstMember', $constraint);
        }

    }

    protected function validateSecondMember(AssuranceEnterpriseImport $assuranceEnterpriseImport, MembersValid $constraint)
    {
        if (!$this->memberIsEmpty($assuranceEnterpriseImport, 'secondMember')) {
            $this->validateMember($assuranceEnterpriseImport, 'secondMember', $constraint);
        }
    }

    protected function validateThirdMember(AssuranceEnterpriseImport $assuranceEnterpriseImport, MembersValid $constraint)
    {
        if (!$this->memberIsEmpty($assuranceEnterpriseImport, 'thirdMember')) {
            $this->validateMember($assuranceEnterpriseImport, 'thirdMember', $constraint);
        }
    }

    protected function validateFourthMember(AssuranceEnterpriseImport $assuranceEnterpriseImport, MembersValid $constraint)
    {
        if (!$this->memberIsEmpty($assuranceEnterpriseImport, 'fourthMember')) {
            $this->validateMember($assuranceEnterpriseImport, 'fourthMember', $constraint);
        }
    }

    protected function validateMember(AssuranceEnterpriseImport $assuranceEnterpriseImport, $prefix, MembersValid $constraint)
    {
        foreach ($constraint->constraints as $property => $constraintsConfig) {
            $method = 'get'. ucfirst($prefix) . ucfirst($property);
            $value = $assuranceEnterpriseImport->{$method}();

            $errors = $this->context->getValidator()->validate($value, $constraintsConfig);

            foreach ($errors as $error) {
                $this->context->buildViolation($error->getMessage())
                    ->atPath($prefix . ucfirst($property))
                    ->addViolation();
            }
        }
        $cityProperty = $prefix . 'BirthCity';
        $cityMethod = 'get' . ucfirst($cityProperty);
        $countryProperty = $prefix . 'BirthCountry';
        $countryMethod = 'get' . ucfirst($countryProperty);
        if (($assuranceEnterpriseImport->{$cityMethod}() && $assuranceEnterpriseImport->{$countryMethod}()) ||
            (!$assuranceEnterpriseImport->{$cityMethod}() && !$assuranceEnterpriseImport->{$countryMethod}())) {
            $this->context->buildViolation('assurance_enterprise_import.birth_place_conflict')
                ->atPath($cityProperty)
                ->addViolation();
        } else {
            if ($assuranceEnterpriseImport->{$cityMethod}()) {
                $cityConstraint = new City(['properties' => [ $prefix . 'BirthCity']]);

                $errors = $this->context->getValidator()->validate($assuranceEnterpriseImport, [$cityConstraint]);

                foreach ($errors as $error) {
                    $this->context->buildViolation($error->getMessage())
                        ->atPath($prefix . 'BirthCity')
                        ->addViolation();
                }
            }
            if ($assuranceEnterpriseImport->{$countryMethod}()) {
                $countryConstraint = new Country(['properties' => [ $prefix . 'BirthCountry']]);

                $errors = $this->context->getValidator()->validate($assuranceEnterpriseImport, [$countryConstraint]);

                foreach ($errors as $error) {
                    $this->context->buildViolation($error->getMessage())
                        ->atPath($prefix . 'BirthCountry')
                        ->addViolation();
                }
            }
        }
    }

    protected function memberIsEmpty(AssuranceEnterpriseImport $assuranceEnterpriseImport, $prefix): bool
    {
        $properties = [
            'firstname',
            'lastname',
            'birthDate',
            'gender',
            'fiscalCode',
            'birthCity',
            'birthCountry',
            'joinDate',
        ];
        foreach ($properties as $property) {
            $property = $prefix . ucfirst($property);
            $method = 'get' . ucfirst($property);
            if ($assuranceEnterpriseImport->{$method}()) {
                return false;
            }
        }
        return true;
    }
}

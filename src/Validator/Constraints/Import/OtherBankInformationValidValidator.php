<?php


namespace App\Validator\Constraints\Import;


use App\Entity\FinancingImport;
use App\Entity\LeasingImport;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class OtherBankInformationValidValidator extends \Symfony\Component\Validator\ConstraintValidator
{

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof OtherBankInformationValid) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\OtherBankInformationValid');
        }

        if (!$value instanceof FinancingImport && !$value instanceof LeasingImport) {
            $supportedClasses = implode(' and ', [ FinancingImport::class, LeasingImport::class ]);
            throw new \LogicException(ConsistentPracticeId::class . ' validator can be applied only on instances of ' . $supportedClasses . ' classes');
        }

        if ($value instanceof FinancingImport) {
            if (strtolower($value->getDbfBank()) === $constraint->keyword) {
                if (!$value->getDbfBusinessName()) {
                    $this->context->buildViolation('financing_import.other_bank_information_valid.business_name')
                        ->setParameter('keyword', $constraint->keyword)
                        ->atPath('dbfBusinessName')
                        ->addViolation();
                }
                if (!$value->getDbfABI()) {
                    $this->context->buildViolation('financing_import.other_bank_information_valid.abi')
                        ->atPath('dbfABI')
                        ->addViolation();
                }
                if (!preg_match('/^\d{5}$/', $value->getDbfABI())) {
                    $this->context->buildViolation('financing_import.other_bank_information_valid.abi_invalid')
                        ->atPath('dbfABI')
                        ->addViolation();
                }
            }
        }

        if ($value instanceof LeasingImport) {
            if (strtolower($value->getSfBankLeasing()) === $constraint->keyword) {
                if (!$value->getSfBusinessName()) {
                    $this->context->buildViolation('leasing_import.other_bank_information_valid.business_name_invalid')
                        ->setParameter('keyword', $constraint->keyword)
                        ->atPath('sfBusinessName')
                        ->addViolation();
                }
            }
        }
    }
}

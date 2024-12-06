<?php


namespace App\Validator\Constraints\Import;


use App\Entity\AssuranceEnterpriseImport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AcApplicationMembersValidValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof AcApplicationMembersValid) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\AcApplicationMembersValid');
        }

        if (!$value instanceof AssuranceEnterpriseImport) {
            throw new \LogicException(AcApplicationMembersValid::class . ' constraint can only be applied to instances of ' . AssuranceEnterpriseImport::class . ' class');
        }

        if (!$value->getAcApplicationMembers()) {
            return;
        }

        $legalFormName = $value->getIbLegalForm();
        $legalForm = $this->entityManager->getRepository(\App\Entity\LegalForm::class)->findOneBy([
            'template' => $value->getApplicationImport()->getTemplate(),
            'name' => $legalFormName
        ]);

        if (!$legalForm || !$legalForm->getCooperative()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('acApplicationMembers')
                ->addViolation();
        }

        if ($legalForm && $legalForm->getCooperative() && $members = $value->getAcApplicationMembers()) {
            $errors = $this->context->getValidator()->validate($members, [ new Type('numeric') ]);

            if ($errors->count() > 0) {
                $this->context->buildViolation($errors->get(0)->getMessage())
                    ->atPath('acApplicationMembers')
                    ->addViolation();
            }
        }
    }
}

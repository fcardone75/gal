<?php


namespace App\Validator\Constraints;


use App\Entity\ApplicationGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AllowProtocolValidator extends \Symfony\Component\Validator\ConstraintValidator
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

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof AllowProtocol) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\UserPassword');
        }

        if (!$value instanceof ApplicationGroup) {
            return;
        }

        /** @var ApplicationGroup|null $entityToSendToNSIA */
        $entityToSendToNSIA = $this->entityManager->getRepository(ApplicationGroup::class)
            ->findOneBy([
                'confidi' => $value->getConfidi(),
                'status' => ApplicationGroup::STATUS_REGISTERED
            ]);
        if ($entityToSendToNSIA && $value->getFilenameFile()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('filenameFile')
                ->setParameter('protocol_number', $entityToSendToNSIA->getProtocolNumber())
                ->setParameter('confidi', $value->getConfidi()->getBusinessName())
                ->addViolation();
        }
    }
}

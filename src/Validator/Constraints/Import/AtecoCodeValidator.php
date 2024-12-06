<?php


namespace App\Validator\Constraints\Import;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AtecoCodeValidator extends \Symfony\Component\Validator\ConstraintValidator
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
        if (!$constraint instanceof AtecoCode) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\AtecoCode');
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        $property = $constraint->withDots ? 'code' : 'codeWithoutDots';

        $existing = $this->entityManager->getRepository(\App\Entity\AtecoCode::class)->findOneBy([
            $property => $value
        ]);

        if (!$existing) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('code', $value)
                ->addViolation();
        }

    }
}

<?php

namespace App\Validator\Constraints;

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use App\Model\UserInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UserPasswordValidator extends ConstraintValidator
{
    /**
     * @var PasswordHasherFactoryInterface
     */
    private $passwordHasherFactory;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        PasswordHasherFactoryInterface $passwordHasherFactory,
        array                          $options = []
    ) {
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->options = $options;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UserPassword) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\UserPassword');
        }

        if (null === $value || '' === $value) {
            $this->context->addViolation($constraint->message);

            return;
        }

        $user = $this->context->getObject()->getParent()->getData();

        if (!$user instanceof UserInterface) {
            throw new ConstraintDefinitionException('The User object must implement the UserInterface interface.');
        }

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);

        if (!$passwordHasher->verify($user->getPassword(), $value, $user->getSalt())) {
            $this->context->addViolation($constraint->message);
        }
    }
}

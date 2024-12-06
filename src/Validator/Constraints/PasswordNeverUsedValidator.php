<?php

namespace App\Validator\Constraints;

use App\Model\UserInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasswordNeverUsedValidator extends ConstraintValidator
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
        if (!$constraint instanceof PasswordNeverUsed) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\PasswordNeverUsed');
        }

        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->context->getObject();

        if (!$user instanceof UserInterface) {
            throw new ConstraintDefinitionException('The User object must implement the UserInterface interface.');
        }

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);

        if ($this->passwordAlreadyUsed($user, $passwordHasher, $value, $constraint->lookBackItems)) {
            $this->context->addViolation($constraint->message);
        }
    }

    protected function passwordAlreadyUsed(UserInterface $user, PasswordHasherInterface $passwordHasher, $value, $lookBackItems)
    {
        $userPasswords = $user->getUserPasswords();

        $userPasswordsToCheck = $userPasswords->slice(0, $lookBackItems);

        return array_filter($userPasswordsToCheck, function(\App\Entity\UserPassword $userPassword) use ($user, $passwordHasher, $value) {
            return $passwordHasher->verify($userPassword->getPasswordHash(), $value, $user->getSalt());
        });
    }
}

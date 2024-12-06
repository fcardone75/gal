<?php


namespace App\Validator\Constraints;

use App\Model\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ChangePasswordFrequencyValidator extends ConstraintValidator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        TranslatorInterface $translator,
        array $options = []
    ) {
        $this->translator = $translator;
        $this->options = $options;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ChangePasswordFrequency) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\UserPassword');
        }

        if (null === $constraint->errorPath || !\is_string($constraint->errorPath)) {
            throw new UnexpectedTypeException($constraint->errorPath, 'string or null');
        }

        if (null === $value || '' === $value || !($value instanceof UserInterface)) {
            return;
        }

        $lastUserPassword = $value->getUserPasswords()->filter(function(\App\Entity\UserPassword $userPassword) use ($value) {
            return $userPassword->getPasswordHash() === $value->getPassword() && !$userPassword->getIsGenerated();
        })->first();

        if ($lastUserPassword && $value->getPlainPassword()) {
            $now = new \DateTime('now', $lastUserPassword->getCreatedAt()->getTimezone());
            if (((int) $now->format('U') - (int) $lastUserPassword->getCreatedAt()->format('U')) <
                $constraint->maxFrequency) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($constraint->errorPath)
                    ->setParameter('{{ allowed_frequency }}', $this->formatSecondsInTimeFormat($constraint->maxFrequency))
                    ->addViolation();
            }
        }
    }

    protected function formatSecondsInTimeFormat($seconds)
    {
        switch (true) {
            case ($seconds/(60*60)) >= 24 && ($seconds/(60*60))%24 === 0:
                $time = $this->translator->trans('change_password_frequency_days', ['count' => $seconds/(60*60*24)], 'validators');
                break;
            default:
                $time = $this->translator->trans('change_password_frequency_hours', ['count' => $seconds/(60*60)], 'validators');
                break;
        }
        return $time;
    }
}

<?php


namespace App\Service\Security;


use App\Entity\LoginFailure;
use App\Model\UserInterface;
use App\Service\Contracts\Security\SecurityManagerInterface;
use App\Service\Contracts\Security\TokenGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityManager implements SecurityManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PasswordHasherFactoryInterface
     */
    private $passwordHasherFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $config;

    /**
     * SecurityManager constructor.
     * @param EntityManagerInterface $entityManager
     * @param UserProviderInterface $userProvider
     * @param TokenGeneratorInterface $tokenGenerator
     * @param TranslatorInterface $translator
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     * @param LoggerInterface $logger
     * @param array $config
     */
    public function __construct(
        EntityManagerInterface         $entityManager,
        UserProviderInterface          $userProvider,
        TokenGeneratorInterface        $tokenGenerator,
        TranslatorInterface            $translator,
        PasswordHasherFactoryInterface $passwordHasherFactory,
        LoggerInterface                $logger,
        array                          $config = []
    )
    {
        $this->entityManager = $entityManager;
        $this->userProvider = $userProvider;
        $this->tokenGenerator = $tokenGenerator;
        $this->translator = $translator;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function setUserLastLogin(UserInterface $user, bool $andFlush = true)
    {
        $lastLogin = new \DateTime();
        $user->setLastLogin($lastLogin);
        if ($andFlush) {
            $this->entityManager->flush();
        }
    }

    public function addLoginFailureForUser(UserInterface $user, Request $request = null, bool $andFlush = true)
    {
        $loginFailure = new LoginFailure();
        $loginFailure
            ->setUser($user)
            ->setRemoteIp($request ? $request->getClientIp() : null);
        $user->addLoginFailure($loginFailure);
        if ($andFlush) {
            $this->entityManager->flush();
        }
    }

    public function prepareUserForCredentialsUpdate(UserInterface $user, bool $andFlush = true)
    {
        $token = strtr(base64_encode(random_bytes(9)), '+/', '-_');
        $user->setUpdatePasswordToken($token);

        if ($andFlush) {
            $this->entityManager->flush();
        }
    }

    public function prepareUserForResetPassword(UserInterface $user, bool $andFlush = true)
    {
        if (isset($this->config['reset_password_min_interval']) &&
            is_numeric($this->config['reset_password_min_interval']) &&
            $user->getResetPasswordRequestedAt() instanceof \DateTimeInterface) {
            $now = new \DateTime('now', $user->getResetPasswordRequestedAt()->getTimezone());
            if (((int)$now->format('U') - (int)$user->getResetPasswordRequestedAt()->format('U')) <
                (int)$this->config['reset_password_min_interval']) {
                throw new \LogicException($this->translator->trans('security.exception.reset_password_already_requested'));
            }
        }
        $user
            ->setResetPasswordToken($this->tokenGenerator->generateToken())
            ->setResetPasswordRequestedAt(new \DateTime());

        if ($andFlush) {
            $this->entityManager->flush();
        }
    }

    public function resetUserPassword(UserInterface $user, $andFlush = true)
    {
        if ($user->getPlainPassword()) {
            $user
                ->setResetPasswordRequestedAt(null)
                ->setResetPasswordToken(null);

            $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);
            $user->setPassword($passwordHasher->hash($user->getPlainPassword(), $user->getSalt()));

            if ($andFlush) {
                $this->entityManager->flush();
            }
        }
    }


    public function loadUser($user)
    {
        if (is_string($user)) {
            try {
                $user = $this->userProvider->loadUserByIdentifier($user);
            } catch (UserNotFoundException $e) {
                $user = null;
            }
        }
        return $user;
    }
}

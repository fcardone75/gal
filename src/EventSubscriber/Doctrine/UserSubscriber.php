<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\User;
use App\Service\Contracts\MailerInterface;
use App\Service\Mailer;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\UnitOfWork;
use App\Entity\UserPassword;
use App\Model\UserInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserSubscriber implements EventSubscriber
{

    /**
     * @var TokenStorageInterface
     */
    private TokenStorageInterface $tokenStorage;

    /**
     * @var Mailer
     */
    private Mailer $mailer;

    /**
     * @var array
     */
    private $config;

    public function __construct(
//        PasswordHasherFactoryInterface $passwordHasherFactory,
        TokenStorageInterface          $tokenStorage,
        MailerInterface                $mailer,
        array                          $config
    ) {
        //$this->passwordHasherFactory = $passwordHasherFactory;
        $this->tokenStorage = $tokenStorage;
        $this->mailer = $mailer;
        $this->config = $config;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
            Events::postLoad
        ];
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        if (isset($this->config['user_class']) && ($userClass = $this->config['user_class'])) {
            $entityInsertions = $eventArgs->getObjectManager()->getUnitOfWork()->getScheduledEntityInsertions();
            $entityUpdates = $eventArgs->getObjectManager()->getUnitOfWork()->getScheduledEntityUpdates();

            foreach ($entityInsertions as $entityInsertion) {
                if (is_a($entityInsertion, $userClass)) {
                    $this->mailer->sendUserCreatedEmail($entityInsertion);
                    $this->addUserPasswordOnFlush($eventArgs, $entityInsertion);
                }
            }

            foreach ($entityUpdates as $entityUpdate) {
                if (is_a($entityUpdate, $userClass)) {
                    $this->addUserPasswordOnFlush($eventArgs, $entityUpdate);
                }
            }
        }
    }

    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        if ($entity instanceof User) {
            if (isset($this->config['password_lifetime'])) {
                $this->setUserCredentialsMustChangeFirstAccess($entity);
            }
            $this->updateLoginFailures($entity);
            try {
                $eventArgs->getObjectManager()->getUnitOfWork()->commit($entity);
            } catch (\Exception $e) {
            }
            $this->setUserLock($entity);
        }
    }

    protected function addUserPasswordOnFlush(OnFlushEventArgs $eventArgs, User $user)
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();
        if ($this->userIsUpdatingPassword($user, $uow)) {
            $userPassword = new UserPassword();

            $userPassword
                ->setUser($user)
                ->setPasswordHash($user->getPassword())
                ->setIsGenerated($user->getPasswordIsGenerated());

            $user->addUserPassword($userPassword);

            $user->eraseCredentials();
            // compute change set on main entity in order to cascade persist associated entities
            $uow->computeChangeSet($em->getClassMetadata(get_class($user)), $user);
        }
    }

    protected function setUserCredentialsMustChangeFirstAccess(UserInterface $user)
    {
        $credentialsExpiresAt = new \DateTime();
        $now = new \DateTime();
        $credentialsChangeFlag = true;
        $configuration = $this->config;
        $forcePasswordChangeNewUsers = $configuration['force_password_change_new_users'] ?? false;
        $forcePasswordChangeNewUsersExcludeRoles = $configuration['force_password_change_new_users_exclude_roles'] ?? [];
        $userGroupShouldChangePasswordAtFirstLogin = !$forcePasswordChangeNewUsersExcludeRoles ||
            !array_intersect($user->getRoles(), $forcePasswordChangeNewUsersExcludeRoles);

        $checkGeneratedPwd = $forcePasswordChangeNewUsers && $userGroupShouldChangePasswordAtFirstLogin && !$user->getLastLogin();

        $pwd = $user->getPassword();
        $currentPasswordIsTheFirst = $user->getUserPasswords()->filter(function(UserPassword $i) use ($pwd, $checkGeneratedPwd){
            $pwdMatch = $i->getPasswordHash() === $pwd;
            $pwdMatch = $pwdMatch && $i->getIsGenerated();
            return $pwdMatch;
        })->first();

        $credentialsChangeFlag = $checkGeneratedPwd && $currentPasswordIsTheFirst ?? false;

        $user->setCredentialsExpiresAt($credentialsExpiresAt);
        $user->setCredentialsExpired($credentialsChangeFlag);
    }

    protected function updateLoginFailures(UserInterface $user)
    {
        if (isset($this->config['login_failures_interval']) &&
            $user->getLoginFailures() &&
            ($lastLoginFailure = $user->getLoginFailures()->first()) &&
            $lastLoginFailureAt = \DateTime::createFromFormat('U', $lastLoginFailure->getCreatedAt()->format('U'))
        ) {
            /** @var \DateTime $lastLoginFailureAt */
            $now = new \DateTime('now', $lastLoginFailureAt->getTimezone());
            $lastLoginFailureAt->add(new \DateInterval(sprintf('PT%sS', $this->config['login_failures_interval'])));

            if ($lastLoginFailureAt->format('U') <= $now->format('U')) {
                foreach ($user->getLoginFailures()->getValues() as $loginFailure) {
                    if (($now->format('U') - $loginFailure->getCreatedAt()->format('U'))  >
                        $this->config['login_failures_interval']) {
                        $user->removeLoginFailure($loginFailure);
                    }
                }
            }
        }
    }

    protected function setUserLock(UserInterface $user)
    {
        if (isset($this->config['lock_after_failures'])) {
            $user->setLocked($user->getLoginFailures() &&
                $user->getLoginFailures()->count() >= $this->config['lock_after_failures']);
        }
    }

    protected function userIsUpdatingPassword(UserInterface $user, UnitOfWork $uow): bool
    {
        return isset($this->config['password_field_name']) &&
            in_array(
                $this->config['password_field_name'],
                array_keys($uow->getEntityChangeSet($user))
            );
    }
}

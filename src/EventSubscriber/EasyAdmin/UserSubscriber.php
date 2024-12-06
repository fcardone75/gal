<?php


namespace App\EventSubscriber\EasyAdmin;


use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserSubscriber implements EventSubscriberInterface
{
    /** @var PasswordHasherFactoryInterface */
    private $passwordHasherFactory;

    /**
     * UserSubscriber constructor.
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     */
    public function __construct(
        PasswordHasherFactoryInterface $passwordHasherFactory
    ) {
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => 'onPersistUser'
        ];
    }

    /**
     * @param BeforeEntityPersistedEvent $event
     */
    public function onPersistUser(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof User) {
            return;
        }

        if ($plainPassword = $entity->getPlainPassword()) {
            $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($entity);
            $entity->setPassword($passwordHasher->hash($plainPassword, $entity->getSalt()));
            $entity->setPasswordIsGenerated(true);
        }
    }
}

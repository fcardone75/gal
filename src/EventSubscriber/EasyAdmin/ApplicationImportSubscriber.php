<?php


namespace App\EventSubscriber\EasyAdmin;


use App\Entity\ApplicationImport;
use App\Service\Contracts\Import\ApplicationImportManagerInterface;
use App\Error\Import\ImportError;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApplicationImportSubscriber implements EventSubscriberInterface
{
    /** @var ApplicationImportManagerInterface */
    private $applicationImportManager;

    /** @var SessionInterface */
    private $session;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * UserSubscriber constructor.
     * @param ApplicationImportManagerInterface $applicationImportManager
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ApplicationImportManagerInterface $applicationImportManager,
        RequestStack                      $requestStack,
        TranslatorInterface               $translator
    )
    {
        $this->applicationImportManager = $applicationImportManager;
        $this->session = $requestStack->getSession();
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => 'beforePersistApplicationImport',
            AfterEntityPersistedEvent::class => 'afterPersistApplicationImport'
        ];
    }

    /**
     * @param BeforeEntityPersistedEvent $event
     * @throws \Exception
     */
    public function beforePersistApplicationImport(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof ApplicationImport) {
            return;
        }

        if ($entity->getFilenameFile()) {
            $this->applicationImportManager->createImport($entity);
        }
    }

    public function afterPersistApplicationImport(AfterEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof ApplicationImport) {
            return;
        }

        if ($entity->getStatus() === ApplicationImport::STATUS_FAILED && $this->session instanceof FlashBagAwareSessionInterface) {
            $this->session->getFlashBag()->add('danger', $this->translator->trans('flash_messages.application_import.import_failed'));
            return;
        }

        if ($entity->getStatus() === ApplicationImport::STATUS_ACQUIRED) {
            $this->applicationImportManager->validate($entity);
            if ($entity->getStatus() === ApplicationImport::STATUS_VALIDATION_FAILED && $this->session instanceof FlashBagAwareSessionInterface) {
                $this->session->getFlashBag()->add('danger', $this->translator->trans('flash_messages.application_import.import_failed'));
                return;
            }
        }

        if ($entity->getStatus() === ApplicationImport::STATUS_VALIDATION_SUCCEEDED) {
            $errors = $this->applicationImportManager->import($entity);

            if ($this->session instanceof FlashBagAwareSessionInterface) {
                if ($entity->getStatus() !== ApplicationImport::STATUS_IMPORTED || $errors) {
                    $this->session->getFlashBag()->add('danger', $this->translator->trans('flash_messages.application_import.import_failed'));
                } else {
                    $this->session->getFlashBag()->add('success', $this->translator->trans('flash_messages.application_import.import_completed'));
                }
            }
        }
    }

}

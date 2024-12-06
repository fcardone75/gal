<?php


namespace App\EventSubscriber\EasyAdmin;


use App\Entity\ApplicationImportTemplate;
use App\Service\Contracts\Import\ApplicationImportTemplateManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApplicationImportTemplateSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ApplicationImportTemplateManagerInterface */
    private $applicationImportTemplateManager;

    /**
     * UserSubscriber constructor.
     * @param EntityManagerInterface $entityManager
     * @param ApplicationImportTemplateManagerInterface $applicationImportTemplateManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ApplicationImportTemplateManagerInterface $applicationImportTemplateManager
    ) {
        $this->entityManager = $entityManager;
        $this->applicationImportTemplateManager = $applicationImportTemplateManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => 'beforePersistApplicationImportTemplate'
        ];
    }

    /**
     * @param BeforeEntityPersistedEvent $event
     * @throws \Exception
     */
    public function beforePersistApplicationImportTemplate(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof ApplicationImportTemplate) {
            return;
        }

        $activeTemplates = $this->entityManager->getRepository(ApplicationImportTemplate::class)
            ->findBy([
                'active' => true
            ]);

        foreach ($activeTemplates as $activeTemplate) {
            $activeTemplate->setActive(false);
        }

        if ($entity->getFilenameFile()) {
            $this->applicationImportTemplateManager->updateReferencesFromTemplate($entity);
        }
    }

}

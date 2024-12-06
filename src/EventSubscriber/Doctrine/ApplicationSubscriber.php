<?php


namespace App\EventSubscriber\Doctrine;


use App\Entity\Application;
use App\Entity\ApplicationStatusLog;
use App\Service\Contracts\ApplicationStatusManagerInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class ApplicationSubscriber implements EventSubscriber
{
    /**
     * @var ApplicationStatusManagerInterface
     */
    private $applicationStatusManager;

    public function __construct(
        ApplicationStatusManagerInterface $applicationStatusManager
    )
    {
        $this->applicationStatusManager = $applicationStatusManager;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::onFlush
        ];
    }

    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        if ($entity instanceof Application) {
            $this->applicationStatusManager->assignStatusToApplication(
                Application::STATUS_CREATED,
                $entity,
                'default_description.created',
                [
                    'practiceId' => $entity->getPracticeId()
                ]
            );
        }
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityUpdates = $eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityUpdates();

        foreach ($entityUpdates as $entityUpdate) {
            if ($entityUpdate instanceof Application) {
                $changeSet = $eventArgs->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entityUpdate);
                if (in_array('applicationGroup', array_keys($changeSet))) {
                    $oldApplicationGroup = array_shift($changeSet['applicationGroup']);
                    $newApplicationGroup = array_pop($changeSet['applicationGroup']);

                    if (null === $newApplicationGroup) {
                        $newStatus = Application::STATUS_CREATED;
                        $applicationGroupId = $oldApplicationGroup->getId();
                        $description = $eventArgs->getEntityManager()->getUnitOfWork()->isScheduledForDelete($oldApplicationGroup) ?
                            'default_description.unlinked_application_group_removed'
                            :
                            'default_description.unlinked';
                    } else {
                        // An Application cannot change its assignment straight forward...
                        if (null !== $oldApplicationGroup) {
                            // ... so reset it to its original ApplicationGroup
                            $entityUpdate->setApplicationGroup($oldApplicationGroup);
                            $classMetadata = $eventArgs->getEntityManager()->getClassMetadata(Application::class);
                            $eventArgs->getEntityManager()->getUnitOfWork()->recomputeSingleEntityChangeSet($classMetadata, $entityUpdate);
                            continue;
                        }
                        $newStatus = Application::STATUS_LINKED;
                        $applicationGroupId = $newApplicationGroup->getId();
                        $description = null;
                    }
                    $this->applicationStatusManager->assignStatusToApplication(
                        $newStatus,
                        $entityUpdate,
                        $description,
                        [
                            'practiceId' => $entityUpdate->getPracticeId(),
                            'applicationGroupId' => $applicationGroupId
                        ]
                    );
                } elseif (in_array('status', array_keys($changeSet))) {
                    $newStatus = array_pop($changeSet['status']);
                    $lastStatusLog = $entityUpdate->getApplicationStatusLogs()->last();
                    if ($lastStatusLog &&
                        ($lastStatusLogStatus = $lastStatusLog->getStatus()) &&
                        $lastStatusLogStatus === $newStatus &&
                        !$lastStatusLog->getId()) {
                        return;
                    }
                    $this->applicationStatusManager->assignStatusToApplication(
                        $newStatus,
                        $entityUpdate,
                        null,
                        [
                            'practiceId' => $entityUpdate->getPracticeId(),
                            'applicationGroupId' => $entityUpdate->getApplicationGroup() ? $entityUpdate->getApplicationGroup()->getId() : null,
                            'protocolNumber' => $entityUpdate->getProtocolNumber()
                        ]
                    );
                }
            }
        }
    }
}

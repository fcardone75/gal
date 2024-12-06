<?php


namespace App\EventSubscriber\Doctrine;


use App\Entity\Application;
use App\Entity\ApplicationGroup;
use App\Entity\ApplicationStatusLog;
use App\Entity\ProtocolNumber;
use App\Service\Contracts\ApplicationGroupManagerInterface;
use App\Service\Contracts\ApplicationStatusManagerInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

class ApplicationGroupSubscriber implements EventSubscriber
{
    /** @var ApplicationGroupManagerInterface  */
    private $applicationGroupManager;

    public function __construct(
        ApplicationGroupManagerInterface $applicationGroupManager
    ) {
        $this->applicationGroupManager = $applicationGroupManager;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
            Events::preRemove
        ];
    }

    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        if ($entity instanceof ApplicationGroup) {
            foreach ($entity->getApplications() as $application) {
                $entity->removeApplication($application);
                $classMetadata = $eventArgs->getEntityManager()->getClassMetadata(Application::class);
                $eventArgs->getEntityManager()->getUnitOfWork()->computeChangeSet($classMetadata, $application);
                // $eventArgs->getEntityManager()->getUnitOfWork()->commit($entity);
            }
        }
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityUpdates = $eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityUpdates();

        foreach ($entityUpdates as $entityUpdate) {
            if ($entityUpdate instanceof ApplicationGroup &&
                $entityUpdate->getStatus() !== ApplicationGroup::STATUS_REGISTERED &&
                $this->isUploadingSignedFile($entityUpdate, $eventArgs->getEntityManager())) {
                $protocolNumber = $this->applicationGroupManager->reserveProtocolNumber($entityUpdate);

                $classMetadata = $eventArgs->getEntityManager()->getClassMetadata(ProtocolNumber::class);
                $eventArgs->getEntityManager()->getUnitOfWork()->computeChangeSet($classMetadata, $protocolNumber);

                $this->applicationGroupManager->protocol($entityUpdate);

                $applicationClassMetadata = $eventArgs->getEntityManager()->getClassMetadata(Application::class);
                foreach ($entityUpdate->getApplications() as $application) {
                    $eventArgs->getEntityManager()->getUnitOfWork()->recomputeSingleEntityChangeSet($applicationClassMetadata, $application);
                }
            }
        }
    }

    protected function isUploadingSignedFile(
        ApplicationGroup $applicationGroup,
        EntityManagerInterface $entityManager
    ): bool
    {
        $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($applicationGroup);

        return in_array(
            'fileUploadedAt',
            array_keys($changeSet)
        );
    }

}

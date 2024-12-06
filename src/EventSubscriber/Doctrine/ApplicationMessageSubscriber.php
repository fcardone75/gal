<?php


namespace App\EventSubscriber\Doctrine;


use App\Entity\ApplicationMessage;
use App\Service\Contracts\ApplicationStatusManagerInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class ApplicationMessageSubscriber implements EventSubscriber
{
    /**
     * @var ApplicationStatusManagerInterface
     */
    private $applicationStatusManager;

    public function __construct(
        ApplicationStatusManagerInterface $applicationStatusManager
    ) {
        $this->applicationStatusManager = $applicationStatusManager;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs  $eventArgs)
    {
        $entityInserts = $eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions();
        $entityUpdates = $eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityUpdates();

        foreach ($entityInserts as $entityInsert) {
            if ($entityInsert instanceof ApplicationMessage) {
                $this->applicationStatusManager->assignInquestStatusToApplication($entityInsert);
            }
        }

        foreach ($entityUpdates as $entityUpdate) {
            if ($entityUpdate instanceof ApplicationMessage) {
                $this->applicationStatusManager->assignInquestStatusToApplication($entityUpdate);
            }
        }
    }
}

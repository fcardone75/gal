<?php

namespace App\EventSubscriber\Loggable;

use App\Entity\ApplicationAttachment;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class LoggableListener extends \Gedmo\Loggable\LoggableListener
{
    protected $entitiesChanged = [];

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        $events = parent::getSubscribedEvents();
        $events[] = Events::preUpdate;
        $events[] = Events::postUpdate;
        return $events;
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $changeSet = $eventArgs->getEntityChangeSet();
        $propertiesChanged = array_keys($changeSet);

        if($entity instanceof ApplicationAttachment && in_array('fileName', $propertiesChanged)) {
            $this->entitiesChanged[] = $entity;
        }
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        if(count($this->entitiesChanged) > 0) {
            foreach ($this->entitiesChanged as $index => $entity) {
                $this->createLogEntry(self::ACTION_UPDATE, $entity, $this->getEventAdapter($eventArgs));
                unset($this->entitiesChanged[$index]);
            }
            $em->flush();
        }
    }
}

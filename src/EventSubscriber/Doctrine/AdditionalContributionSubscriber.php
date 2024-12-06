<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\AdditionalContribution;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use App\Entity\UserPassword;
use App\Model\UserInterface;

class AdditionalContributionSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::postLoad
        ];
    }

    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        if ($entity instanceof AdditionalContribution) {
            $entity->setFormOrderBy(AdditionalContribution::$formOrderByMap[$entity->getType()]);
        }
    }
}

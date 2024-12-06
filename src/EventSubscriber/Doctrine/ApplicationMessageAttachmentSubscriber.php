<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\ApplicationMessageAttachment;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Routing\RouterInterface;

class ApplicationMessageAttachmentSubscriber implements EventSubscriber
{
    /**
     * @var RouterInterface
     */
    private $router;

    private $params;


    /**
     * NewsListener constructor.
     * @param RouterInterface $router
     * @param $params
     */
    public function __construct(RouterInterface $router, $params)
    {
        $this->router = $router;
        $this->params = $params;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postLoad,
            Events::postPersist
        ];
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof ApplicationMessageAttachment) {
            $entity->setFileWebPath($this->generateFileWebPathUrl($entity));
            $entity->setApiDelete($this->generateDeleteAttachmentUrl($entity));
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof ApplicationMessageAttachment) {
            $entity->setFileWebPath($this->generateFileWebPathUrl($entity));
            $entity->setApiDelete($this->generateDeleteAttachmentUrl($entity));
        }
    }

    private function generateDeleteAttachmentUrl(ApplicationMessageAttachment $entity)
    {
        return $this->router->generate('app_applicationmessageattachment_delete', ['id' => $entity->getId()]);
    }

    private function generateFileWebPathUrl(ApplicationMessageAttachment $entity)
    {
        return $this->router->generate('app_applicationmessageattachment_download', ['id' => $entity->getId()]);
    }
}

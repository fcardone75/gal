<?php


namespace App\EventSubscriber\EasyAdmin;


use App\Controller\Admin\ApplicationGroupCrudController;
use App\Entity\Application;
use App\Entity\ApplicationGroup;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ApplicationGroupSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var User */
    private $user;
    /**
     * @var AdminUrlGenerator
     */
    private $adminUrlGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        AdminUrlGenerator $adminUrlGenerator
    ) {
        $this->entityManager = $entityManager;
        if ($tokenStorage->getToken() &&
            ($user = $tokenStorage->getToken()->getUser()) &&
            $user instanceof User) {
            $this->user = $user;
        }
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeCrudActionEvent::class => 'onBeforeCrudAction',
            AfterCrudActionEvent::class => 'onAfterCrudAction'
        ];
    }

    public function onBeforeCrudAction(BeforeCrudActionEvent $event)
    {
        // automatically create an application group assigned to current user's confidi and redirect to edit
        // if current user is associated with a confidi
        if ($event->getAdminContext()->getCrud()->getCurrentAction() === Action::NEW &&
            $event->getAdminContext()->getCrud()->getEntityFqcn() === ApplicationGroup::class &&
            $this->user &&
            $this->user->getConfidi()
        ) {
            $applicationGroup = new ApplicationGroup();

            $applicationGroup->setConfidi($this->user->getConfidi());

            $this->entityManager->persist($applicationGroup);
            $this->entityManager->flush();

            $url = $this->adminUrlGenerator
                ->setController(ApplicationGroupCrudController::class)
                ->setAction(Action::EDIT)
                ->setEntityId($applicationGroup->getId());
            $event->setResponse(new RedirectResponse($url));
        }
    }

    public function onAfterCrudAction(AfterCrudActionEvent $event)
    {
        if (in_array($event->getAdminContext()->getCrud()->getCurrentAction(), [ Action::NEW, Action::EDIT ]) &&
            $event->getAdminContext()->getCrud()->getEntityFqcn() === ApplicationGroup::class) {
            /** @var ApplicationGroup $applicationGroup */
            $applicationGroup = $event->getAdminContext()->getEntity()->getInstance();

            $unlinkedApplications = [];

            if ($applicationGroup) {
                $unlinkedApplications = $this->entityManager->getRepository(Application::class)->findBy([
                    'status' => Application::STATUS_CREATED,
                    'confidi' => $applicationGroup->getConfidi()
                ]);
            }

            $event->addResponseParameters([
                'unlinkedApplications' => $unlinkedApplications
            ]);
        }

    }
}

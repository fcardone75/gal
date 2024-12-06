<?php


namespace App\EventSubscriber\EasyAdmin;


use App\Entity\Application;
use App\Entity\FinancingProvisioningCertification;
use App\Entity\User;
use App\Entity\ApplicationMessage;
use App\Form\ApplicationAdditionalContributionCollectionType;
use App\Form\ApplicationAttachmentCollectionType;
use App\Form\ApplicationMessageType;
use App\Form\FinancingProvisioningCertificationPdfType;
use App\Form\FinancingProvisioningCertificationType;
use App\Service\ApplicationFormManager;
use App\Service\Contracts\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class ApplicationSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var ApplicationFormManager */
    private $applicationFormManager;

    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * UserSubscriber constructor.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory,
        ApplicationFormManager $applicationFormManager,
        MailerInterface $mailer
    ) {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->applicationFormManager = $applicationFormManager;
        $this->mailer = $mailer;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterCrudActionEvent::class => 'afterCrudActionEvent'
        ];
    }

    /**
     * @param AfterCrudActionEvent $event
     * @throws \Exception
     */
    public function afterCrudActionEvent(AfterCrudActionEvent $event)
    {
        $viewVars = [];
        $adminContext = $event->getAdminContext();
        if ($adminContext->getCrud()->getCurrentAction() === Action::DETAIL &&
            $adminContext->getCrud()->getEntityFqcn() === Application::class)
        {
            /** @var Application $application */
            $application = $adminContext->getEntity()->getInstance();
            /** @var User $loggedUser */
            $loggedUser = $adminContext->getUser();
            /** @var ApplicationMessage $applicationMessageDraft */
            $applicationMessageDraft = $application->getFirstMessageDraftOfUser($loggedUser);

            $applicationMessageForm = $this->formFactory->create(ApplicationMessageType::class, $applicationMessageDraft ?: null, ['attr' => ['application_id' => $application->getId()]]);
            $applicationAttachmentsForm = $this->formFactory->create(ApplicationAttachmentCollectionType::class, $application);
            if($this->applicationFormManager->canEditForm($application, ApplicationFormManager::APPLICATION_ADDITIONAL_CONTRIBUTIONS_FORM)) {
                $application->createNotImportedAdditionalContributionItems();
            }
            $applicationAdditionalContributionsForm = $this->formFactory->create(ApplicationAdditionalContributionCollectionType::class, $application);

            $this->handleFinancingProvisioningCertification($application, $adminContext->getRequest(),$viewVars);
            $this->handleFinancingProvisioningCertificationPdfSigned($application, $adminContext->getRequest(),$viewVars);

            $viewVars = array_merge($viewVars, [
                "applicationMessageForm" => $applicationMessageForm->createView(),
                "applicationAttachmentsForm" => $applicationAttachmentsForm->createView(),
                "applicationAdditionContributionsForm" => $applicationAdditionalContributionsForm->createView(),
                "canEditApplicationMessageForm" => $this->applicationFormManager->canEditForm($application, ApplicationFormManager::APPLICATION_MESSAGE_FORM)
            ] );
            $event->addResponseParameters($viewVars);
        }
    }

    protected function handleFinancingProvisioningCertificationPdfSigned(Application $application, Request $request, array &$viewVars){
        $entity = $this->getFinancingProvisioningCertification($application);
        $form = $this->formFactory->create(FinancingProvisioningCertificationPdfType::class, $entity, []);
        if($request->getMethod() === Request::METHOD_POST){
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                $entity->setStatus(FinancingProvisioningCertification::STATUS_COMPLETED);
                $this->entityManager->flush();
                $this->mailer->sendFPCertificationNotification($entity);
            }
        }
        $viewVars['applicationFinancingProvisioningCertificationPdfForm'] = $form->createView();
    }

    protected function handleFinancingProvisioningCertification(
        Application $application,
        Request $request,
        array &$viewVars
    )
    {
        $entity = $this->getFinancingProvisioningCertification($application);
        $form = $this->formFactory->create(FinancingProvisioningCertificationType::class, $entity);
        if($request->getMethod() === Request::METHOD_POST){
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
//                $entity->setStatus(FinancingProvisioningCertification::STATUS_COMPLETED);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                $this->entityManager->refresh($entity);
                $form = $this->formFactory->create(FinancingProvisioningCertificationType::class, $entity);

            }
        }
        $viewVars['applicationFinancingProvisioningCertificationForm']= $form->createView();
    }

    protected function getFinancingProvisioningCertification(Application $application) : FinancingProvisioningCertification
    {
        $entity = $application->getFinancingProvisioningCertification();
        if(!$entity){
            $entity = new FinancingProvisioningCertification();
            $entity->setApplication($application);
            if ($application->getFDfAmount()){
                $entity->setAmount($application->getFDfAmount());
            }
            if ($application->getFDfIssueDate()){
                $entity->setIssueDate($application->getFDfIssueDate());
            }
            if ($application->getFDfDuration() ){
                $entity->setFinancingDuration($application->getFDfDuration());
            }
        }
        return $entity;
    }
}

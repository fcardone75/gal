<?php

namespace App\Controller\Admin;

use App\Admin\Field\ApplicationStatusLogField;
use App\Entity\Application;
use App\Entity\ApplicationAttachment;
use App\Entity\ApplicationMessage;
use App\Entity\ApplicationMessageAttachment;
use App\Entity\Confidi;
use App\Entity\User;
use App\Form\Api\ApplicationMessageType;
use App\Form\ApplicationAdditionalContributionCollectionType;
use App\Form\ApplicationAttachmentCollectionType;
use App\Security\Http\Permissions\ApplicationVoter;
use App\Service\ApplicationFormManager;
use App\Service\Contracts\ApplicationStatusManagerInterface;
use App\Service\Contracts\Export\ApplicationExportManagerInterface;
use App\Service\Contracts\MailerInterface;
use App\Service\Contracts\ZipperInterface;
use App\Validator\Constraints\FileP7M;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use App\Filter\ApplicationContrattoDataFirma;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApplicationCrudController extends AbstractCrudController
{
    const ACTION_SET_INQUEST_STATUS_CLOSED = 'set_inquest_status_closed btn btn-link pr-0';
    const ACTION_DOWNLOAD_APPLICATION_ZIP = 'download_application_zip btn btn-link pr-0';
    const ACTION_DOWNLOAD_APPLICATION_CSV = 'download_application_csv btn btn-link pr-0';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ApplicationFormManager
     */
    private $applicationFormManager;

    /**
     * @var ZipperInterface
     */
    private $zipper;

    /**
     * @var FilesystemMap
     */
    private $filesystemMap;


    /**
     * @var ApplicationExportManagerInterface
     */
    private $applicationExportManager;
    /**
     * @var ApplicationStatusManagerInterface
     */
    private $applicationStatusManager;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ApplicationFormManager $applicationFormManager,
        ZipperInterface $zipper,
        FileSystemMap $filesystemMap,
        ApplicationExportManagerInterface $applicationExportManager,
        ApplicationStatusManagerInterface $applicationStatusManager
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->applicationFormManager = $applicationFormManager;
        $this->zipper = $zipper;
        $this->filesystemMap = $filesystemMap;
        $this->applicationExportManager = $applicationExportManager;
        $this->applicationStatusManager = $applicationStatusManager;
    }

    public static function getEntityFqcn(): string
    {
        return Application::class;
    }


    public function downloadApplicationCsv(): Response
    {
        $criteria = [];
//        if (!empty($confidiSelected)) {
//            $criteria['confidi'] = $confidiSelected;
//        }
//
//        if (isset($config_chart['chart_contribution_type'])) {
//            $criteria['contribution_type'] = $config_chart['chart_contribution_type'];
//        }
        if (($user = $this->getUser()) && $user->getConfidi()) {
            $criteria['confidi'] = $user->getConfidi()->getId();
        }

        $applications = $this->entityManager->getRepository(Application::class)->findAllForDashboard($criteria);
        return $this->applicationExportManager->createApplicationsCsv($applications);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->overrideTemplate('crud/detail', 'bundles/EasyAdminBundle/application/crud/detail.html.twig')
            ->setEntityLabelInSingular('crud.application.singular')
            ->setEntityLabelInPlural('crud.application.plural')
            ->setEntityPermission(ApplicationVoter::APPLICATION_VIEW_CONFIDI);
    }

    public function configureAssets(Assets $assets): Assets
    {
        //$assets->addWebpackEncoreEntry('field-collection');
        return $assets;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')
                ->setChoices($this->applicationStatusManager->getStatusAsOptionArray())
                ->canSelectMultiple()
            )
            ->add(
                DateTimeFilter::new('fDfIssueDate')
                    ->setLabel($this->translator->trans('crud.application.grid.column_label.financing_issue_date'))
            )
//TODO: verificare
            ->add(
                ApplicationContrattoDataFirma::new('contrattoDataFirma')
                    ->setLabel($this->translator->trans('crud.application.grid.column_label.signature_contract_date'))
                    // WARNING: do not remove/change the following line, since it disables datetime-local type rendering on html input
                    ->setFormTypeOptions(['value_type' => DateType::class])
            )
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $setInquestStatusClosedAction = Action::new(
            self::ACTION_SET_INQUEST_STATUS_CLOSED,
            'crud.application.actions.set_inquest_status_closed',
            'fas fa-comment-slash'
        )->linkToCrudAction('setInquestStatusClosed');

        $downloadAppZipAction = Action::new(
            self::ACTION_DOWNLOAD_APPLICATION_ZIP,
            'crud.application.actions.download_application_zip',
            'fas fa-file-archive'
        )->linkToCrudAction('downloadApplicationZip');

        $downloadApplicationCsv = Action::new(
            self::ACTION_DOWNLOAD_APPLICATION_CSV,
            'crud.application.actions.download_application_csv',
            'fas fa-file-csv'
        )->linkToCrudAction('downloadApplicationCsv')
            ->createAsGlobalAction();

        return $actions
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $downloadApplicationCsv)
            ->add(Crud::PAGE_DETAIL, $setInquestStatusClosedAction)
            ->add(Crud::PAGE_DETAIL, $downloadAppZipAction)
            ->add(Crud::PAGE_DETAIL, Action::DELETE)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function(Action $action){
                return $action
                    ->displayIf(function(Application $a){
                        return $a->canBeDeleted();
                    });
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function(Action $action){
                return $action
                    ->displayIf(function(Application $a){
                        return $a->canBeDeleted();
                    });
            })
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->setPermissions([
                Action::INDEX => 'ROLE_APPLICATION_INDEX',
                Action::DETAIL => 'ROLE_APPLICATION_DETAIL',
                Action::NEW => 'false',
                Action::EDIT => false,
                Action::DELETE => 'ROLE_APPLICATION_DELETE',
                self::ACTION_SET_INQUEST_STATUS_CLOSED => 'ROLE_OPERATORE_ARTIGIANCASSA'
            ])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'crud.application.grid.column_label.practice_web_id')
            ->hideOnForm();
        yield TextField::new('practiceId', 'crud.application.grid.column_label.practice_id');
        yield TextField::new('flagEnergia', 'crud.application.grid.column_label.flag_energia');
        yield TextField::new('nsiaNumeroPosizione', 'crud.application.grid.column_label.nsia_id');
        $isConfidiUser = ($user = $this->getUser()) &&
            $user instanceof User &&
            $user->getConfidi();

        $confidiField = AssociationField::new('confidi', 'crud.application.grid.column_label.confidi')
            ->setCssClass($isConfidiUser ? 'field-association hidden' : 'field-association')
            ->setFormTypeOption('attr', [
                'readonly' => $isConfidiUser
            ])
            ->setFormTypeOption('disabled', $isConfidiUser);
        if ($isConfidiUser) {
            $confidiField->hideOnIndex();
        }
        yield $confidiField;
        yield TextField::new('aeIbBusinessName', 'crud.application.grid.column_label.ae_ib_business_name');
        yield ChoiceField::new('status', 'crud.application.grid.column_label.status')
            ->setChoices($this->applicationStatusManager->getStatusAsOptionArray())
            ->formatValue(function($value){
                return $this->translator->trans($value, [], 'application_status');
            });
        yield TextField::new('inquestStatus', 'crud.application.grid.column_label.inquest_status')
            ->formatValue(function($value){
                return $this->translator->trans($value, [], 'application_inquest_status');
            });
        yield DateField::new('fDfIssueDate', 'crud.application.grid.column_label.financing_issue_date')
            ->setFormat('dd/MM/Y')
            ->onlyOnIndex()
        ;
        yield DateField::new('contrattoDataFirma', 'crud.application.grid.column_label.signature_contract_date')
            ->setFormat('dd/MM/Y')
            ->onlyOnIndex()
        ;
//        yield DateField::new('nsiaNota', 'crud.application.grid.column_label.nsia_nota')
//            ->onlyOnIndex()
//        ;
        yield ApplicationStatusLogField::new('applicationStatusLogs', 'crud.application.grid.column_label.status_history')
            ->onlyOnDetail();
    }

    public function setInquestStatusClosed(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator
    ): RedirectResponse
    {
        /** @var Application $entity */
        $entity = $context->getEntity()->getInstance();
        $entity->setInquestStatus(Application::INQUEST_STATUS_CLOSED);
        $this->entityManager->flush();

        $url = $adminUrlGenerator
            ->setAction(Action::DETAIL)
            ->setEntityId($context->getEntity()->getPrimaryKeyValue())
            ->generateUrl();

        return $this->redirect($url);
    }

    public function downloadApplicationZip(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator
    )
    {
        /** @var Application $application */
        $application = $context->getEntity()->getInstance();
        $applicationId = $application->getId();

        $applicationAttachments = $application->getApplicationAttachments()->getValues();
        $applicationMessageAttachments = [];
        /** @var ApplicationMessage $appMessage */
        foreach ($application->getPublishedMessages() as $appMessage) {
            /** @var ApplicationMessageAttachment $appMessageAtt */
            foreach ($appMessage->getAttachments() as $appMessageAtt) {
                $applicationMessageAttachments [] = $appMessageAtt;
            }
        }

        $filesMap = [];

        /** @var ApplicationAttachment $attachment */
        foreach ($applicationAttachments as $attachment) {
            $filesMap[] = [
                'fileKey' => $attachment->getFileName(),
                'fileSystem' => $this->filesystemMap->get('application_attachment')
            ];
        }

        /** @var ApplicationMessageAttachment $attachment */
        foreach ($applicationMessageAttachments as $attachment) {
            $filesMap[] = [
                'fileKey' => $attachment->getFileName(),
                'fileSystem' => $this->filesystemMap->get('application_message_attachment')
            ];
        }

        if($application->getApplicationGroup()) {
            if($fileName = $application->getApplicationGroup()->getFilename()) {
                $filesMap[] = [
                    'fileKey' => $fileName,
                    'fileSystem' => $this->filesystemMap->get('application_group')
                ];
            }
        }

        if($application->getFinancingProvisioningCertification()) {
            if($fileName = $application->getFinancingProvisioningCertification()->getFilename()) {
                $filesMap[] = [
                    'fileKey' => $fileName,
                    'fileSystem' => $this->filesystemMap->get('financing_provisioning')
                ];
            }
        }

        if(count($filesMap) > 0) {
            return $this->zipper->getResponseFromZipFiles($filesMap, $application->getPracticeId() . '-fascicolo.zip');
        } else {
            $this->addFlash('danger', 'Errore! Non sono presenti allegati per la domanda');
            $url = $adminUrlGenerator
                ->setAction(Action::DETAIL)
                ->setEntityId($applicationId)
                ->generateUrl();

            return $this->redirect($url);
        }
    }

    public function processApplicationAttachmentCollectionForm(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator,
        ManagerRegistry $managerRegistry,
        Request $request,
        ValidatorInterface $validator,
        TranslatorInterface $translator
    ): RedirectResponse
    {
        /** @var Application $entity */
        $entity = $context->getEntity()->getInstance();

        $url = $adminUrlGenerator
            ->setAction(Action::DETAIL)
            ->setEntityId($entity->getId());


        $form = $this->createForm(ApplicationAttachmentCollectionType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /**
             * The method $form->isValid() cannot be used in this particular case because it is part of
             * a complex form that is improperly configured, leading to a persistent issue with additional,
             * non-allowed fields.
             * This misconfiguration flaw results in the method consistently encountering validation errors,
             * making it unsuitable for use in its intended context.
             * Therefore, to circumvent this issue and ensure proper validation,
             * a local validation process specifically for the file format is implemented instead.
             * This approach allows for targeted validation of the file format,
             * bypassing the broader form validation issues caused by the form's configuration.
             *
             * gabriele.cavigiolo@synesthesia.it
             *
             * if ($form->isValid()) {
             *
             */

            $application = $form->getData();
            if ($application instanceof Application) {
                /**
                 * With the current form management implementation, it is not possible to directly retrieve the most recently
                 * added attachment unless by searching for it within the collection as a non-persisted entity,
                 * thus with an empty or null ID. This limitation arises because the form handling process does not automatically
                 * persist newly added entities to the database immediately upon their addition to the collection.
                 * Instead, these entities remain in memory as part of the collection associated with the parent entity
                 * until an explicit save or persist operation is performed.
                 *
                 * To identify and work with the latest added attachment,
                 * one must iterate through the collection, focusing on entities that have not yet been assigned
                 * an ID (indicating they have not been persisted to the database).
                 * This approach involves manually checking each entity within the collection for an empty ID and determining
                 * the one that represents the most recent addition based on your application's specific
                 * logic or criteria (e.g., a timestamp field if available).
                 *
                 * This method of identifying the latest attachment is necessary due to
                 * the nature of how entities are managed within a Symfony application using Doctrine ORM.
                 * Until entities are persisted and flushed to the database, they do not receive a database-generated identifier (ID),
                 * making direct retrieval based on ID impossible for entities that are newly added to a collection and not yet saved.
                 */
                $uploadedFile = "";
                foreach ($application->getApplicationAttachments() as $attachment) {
                    if (empty($attachment->getId())) {
                        $uploadedFile = $attachment;
                        break;
                    }
                }
                /** @var UploadedFile $file */
                $file = $uploadedFile->getUploadFile();
                $constraints = new Assert\File([
                    'mimeTypes' => ['application/pdf', 'application/x-pkcs7-mime', 'application/octet-stream'],
                    'mimeTypesMessage' => 'file_p7m_mime_type',
                ]);

                $errors = $validator->validate($file, $constraints);
                if ($file->getMimeType() == 'application/x-pkcs7-mime' || $file->getClientOriginalExtension() == 'p7m') {
                    $errors = $validator->validate($file, new FileP7M());
                }
                if (0 === count($errors)) {
                    $em = $managerRegistry->getManager();
                    $em->flush();
                    $this->addFlash('success', $translator->trans('flash_messages.attachment_inserted'));
                } else {
                    $message = [];
                    foreach ($errors as $error) {
                        $message[] = $error->getMessage();
                    }
                    $this->addFlash('error', implode('\n',$message));
                    $url->generateUrl();
                }
            }
        }
        return $this->redirect($url);
    }

    public function processApplicationAdditionalContributionCollectionForm(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator,
        ManagerRegistry $managerRegistry,
        Request $request
    ): RedirectResponse
    {
        /** @var Application $entity */
        $entity = $context->getEntity()->getInstance();

        $url = $adminUrlGenerator
                ->setAction(Action::DETAIL)
                ->setEntityId($entity->getId())
                ->generateUrl()."#application_additional_contributions_focus";

        $form = $this->createForm(ApplicationAdditionalContributionCollectionType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $em = $managerRegistry->getManager();
            $em->flush();
        }
        return $this->redirect($url);
    }

    #[Route(path: '/applications/{id}/messages', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function postMessagesAction(
        MailerInterface $mailer,
        ManagerRegistry $managerRegistry,
        Request $request,
        ValidatorInterface $validator,
        $id
    ): JsonResponse
    {
        $em = $managerRegistry->getManager();

        /** @var Application $application */
        $application = $em->getRepository(Application::class)->find($id);

        if (!$application) {
            throw $this->createNotFoundException('Application not found');
        }

//        if (!$this->applicationFormManager->canEditForm($application, ApplicationFormManager::APPLICATION_MESSAGE_FORM)) {
//            throw $this->createAccessDeniedException();
//        }

        $applicationMessage = new ApplicationMessage();

        $form = $this->createForm(ApplicationMessageType::class, $applicationMessage);

        $form->handleRequest($request);

        $response = new JsonResponse();

        if ($form->isSubmitted()) {
            // Message attachment validation
            $errors_messages = $this->validateChatMessagesAttachment($applicationMessage, $validator);

            if ($form->isValid() && count($errors_messages) === 0) {
                $applicationMessage->setApplication($application);
                $applicationMessage->setUpdatedAt(new \DateTime());
                $text = $applicationMessage->getText();
                $applicationMessage->setText(htmlspecialchars(strip_tags($text)));
                $em->persist($applicationMessage);
                $em->flush();

                if($applicationMessage->getPublished()) {
                    $mailer->sendApplicationMessageNotification($applicationMessage);
                }

                $response
                    ->setData($this->getApplicationMessageArray($applicationMessage))
                    ->setStatusCode(Response::HTTP_CREATED)
                ;
                return $response;
            }
        }
        $list_errors = $form->getErrors(true);

        foreach ($list_errors as $error) {
            /** @var FormError $error */
            $errors_messages[] = $error->getMessage();
        }

        $response
            ->setData(json_encode(["messageErrors" => $errors_messages]))
            ->setStatusCode(Response::HTTP_BAD_REQUEST)
        ;
        return $response;
    }

    /**
     *
     * @Route(path="/applications/{id}/messages/{messageId}", methods={"PATCH"}, requirements={"id": "\d+"})
     */
    public function patchMessagesAction(
        MailerInterface $mailer,
        ManagerRegistry $managerRegistry,
        Request $request,
        ValidatorInterface $validator,
        $id,
        $messageId
    ): JsonResponse
    {
        $request->request->remove('_method');
        $em = $managerRegistry->getManager();

        /** @var Application $application */
        $application = $em->getRepository(Application::class)->find($id);

        if (!$application) {
            throw $this->createNotFoundException('Application not found');
        }

        if (!$this->applicationFormManager->canEditForm($application, ApplicationFormManager::APPLICATION_MESSAGE_FORM)) {
            throw $this->createAccessDeniedException();
        }

        $applicationMessage = $em->getRepository(ApplicationMessage::class)->find($messageId);

        if (!$applicationMessage) {
            throw $this->createNotFoundException('Message not found');
        }

        $form = $this->createForm(ApplicationMessageType::class, $applicationMessage, ['method' => 'PATCH']);

        $form->handleRequest($request);

        $response = new JsonResponse();

        if ($form->isSubmitted()) {
            // Message attachment validation
            $errors_messages = $this->validateChatMessagesAttachment($applicationMessage, $validator);
            /**
             * Here is needed control and manage $form without uploaded file
             * but with an existent message attachment.
             * Without passing this logic, $form->isValid() will be always false because
             * here comes an entity without an uploaded file as expected by default from framework.
             */
            $list_errors = $form->getErrors(true);
            $messages = [];
            $by_pass_file = false;
            if ($list_errors->count() === 1) {
                $error = $list_errors->offsetGet(0);
                if ($error->getCause()->getPropertyPath() == "children[attachments].children[0].children[uploadFile]") {
                    $by_pass_file = true;
                }
            }
            foreach ($list_errors as $error) {
                /** @var FormError $error */
                $messages[] = $error->getMessage();
            }
            if (($form->isValid() || ($form->isValid() === false && $by_pass_file === true)) && count($errors_messages) === 0) {

                $applicationMessage->setApplication($application);
                $applicationMessage->setUpdatedAt(new \DateTime());
                $text = $applicationMessage->getText();
                $applicationMessage->setText(htmlspecialchars(strip_tags($text)));
                $em->flush();

                if($applicationMessage->getPublished()) {
                    $mailer->sendApplicationMessageNotification($applicationMessage);
                }

                $response
                    ->setData($this->getApplicationMessageArray($applicationMessage))
                    ->setStatusCode(Response::HTTP_CREATED)
                ;
                return $response;

            }
        }

        $response
            ->setData(json_encode(["messageErrors" => $messages]))
            ->setStatusCode(Response::HTTP_BAD_REQUEST)
        ;
        return $response;
    }

    private function getApplicationMessageArray(ApplicationMessage $applicationMessage): array
    {
        return [
            "id" => $applicationMessage->getId(),
            "text" => $applicationMessage->getText(),
            "published" => $applicationMessage->getPublished(),
            "created_at" => $applicationMessage->getCreatedAt(),
            "updated_at" => $applicationMessage->getUpdatedAt(),
            "created_by" => [
                "id" => $applicationMessage->getCreatedBy()->getId(),
                "firstname" => $applicationMessage->getCreatedBy()->getFirstName(),
                "username" => $applicationMessage->getCreatedBy()->getUsername()
            ],
            "attachments" => $applicationMessage->getAttachments()->map(function (ApplicationMessageAttachment  $applicationMessageAttachment)
            {
                return [
                    "api_delete" => $applicationMessageAttachment->getApiDelete(),
                    "file_web_path" => $applicationMessageAttachment->getFileWebPath(),
                    "original_filename" => $applicationMessageAttachment->getOriginalFileName()
                ];
            })->toArray()
        ];
    }

    public function autocomplete(AdminContext $context): JsonResponse
    {
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), FieldCollection::new([]), FilterCollection::new());

        $rootAliases = $queryBuilder->getRootAliases();
        $rootAlias = $rootAliases ? array_shift($rootAliases) : null;
        $statusField = $rootAlias ? implode('.', [$rootAlias, 'status']) : 'status';

        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq($statusField, ':status'))
            ->setParameter('status', Application::STATUS_CREATED);

        $autocompleteContext = $context->getRequest()->get(AssociationField::PARAM_AUTOCOMPLETE_CONTEXT);

        /** @var CrudControllerInterface $controller */
        $controller = $this->get(ControllerFactory::class)->getCrudControllerInstance($autocompleteContext[EA::CRUD_CONTROLLER_FQCN], Action::INDEX, $context->getRequest());
        /** @var FieldDto $field */
        $field = FieldCollection::new($controller->configureFields($autocompleteContext['originatingPage']))->getByProperty($autocompleteContext['propertyName']);
        /** @var \Closure|null $queryBuilderCallable */
        $queryBuilderCallable = $field->getCustomOption(AssociationField::OPTION_QUERY_BUILDER_CALLABLE);

        if (null !== $queryBuilderCallable) {
            $queryBuilderCallable($queryBuilder);
        }

        $paginator = $this->get(PaginatorFactory::class)->create($queryBuilder);

        return JsonResponse::fromJsonString($paginator->getResultsAsJson());
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if (($user = $this->getUser()) && $user instanceof User && $user->getConfidi()) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('entity.confidi', ':confidi')
                )
                ->setParameter('confidi', $user->getConfidi()->getId());
        }

        return $qb;
    }

    protected function getCurrentUserConfidi(): ?Confidi
    {
        return $this->getUser() ? $this->getUser()->getConfidi() : null;
    }

    /**
     * @param ApplicationMessage $applicationMessage
     * @param ValidatorInterface $validator
     * @return array
     */
    protected function validateChatMessagesAttachment(
        ApplicationMessage $applicationMessage,
        ValidatorInterface $validator): array
    {
        $errors_messages = [];
        /**
         * With the current form management implementation, it is not possible to directly retrieve the most recently
         * added attachment unless [...] see line 406
         */
        $uploadedFile = "";
        foreach ($applicationMessage->getAttachments() as $attachment) {
            $uploadedFile = $attachment;
        }
        if (!empty($uploadedFile)) {
            /**
             * NB: will be UploadedFile instance only if we are treating a straight message or
             * a draft is changed.
             * Otherwise, UploadedFile will not be available.
             * So we need to check the existence.
             */
            /** @var UploadedFile $file */
            $file = $uploadedFile->getUploadFile();
            if ($file instanceof UploadedFile) {
                $constraints = new Assert\File([
                    'mimeTypes' => ['application/pdf', 'application/x-pkcs7-mime'],
                    'mimeTypesMessage' => 'file_p7m_mime_type',
                ]);

                $errors = $validator->validate($file, $constraints);
                if ($file->getMimeType() == 'application/x-pkcs7-mime' || $file->getClientOriginalExtension() == 'p7m') {
                    $errors = $validator->validate($file, new FileP7M());
                }
                if ($errors->count() > 0) {
                    foreach ($errors as $error) {
                        $errors_messages[] = $error->getMessage();
                    }
                }
            }
        }
        return $errors_messages;
    }
}

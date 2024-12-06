<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Model\User\CsvImporter;
use App\Service\Contracts\Security\RoleProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCrudController extends AbstractCrudController
{
    /**
     * @var RoleHierarchyInterface
     */
    private $roleProvider;

    /**
     * @var CsvImporter
     */
    private $csvImporter;

    /**
     * @var SluggerInterface
     */
    private $slugger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AdminUrlGenerator
     */
    private $adminUrlGenerator;

    public function __construct(
        RoleProviderInterface $roleProvider,
        CsvImporter $csvImporter,
        SluggerInterface $slugger,
        TranslatorInterface $translator,

        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator
    ) {
        $this->roleProvider = $roleProvider;
        $this->csvImporter = $csvImporter;
        $this->slugger = $slugger;
        $this->translator = $translator;

        $this->entityManager = $entityManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('crud.users.singular')
            ->setEntityLabelInPlural('crud.users.plural')
            ->overrideTemplate('crud/edit', 'bundles/EasyAdminBundle/user/crud/edit.html.twig')
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $importAction = Action::new('import', 'crud.users.actions.import', 'fa fa-upload')
            ->createAsGlobalAction()
            ->linkToCrudAction('import')
            ->addCssClass('btn btn-primary');

        $removeMfaAction = Action::new('btn btn-danger', 'Disabilita MFA', 'fa fa-key')
            ->linkToCrudAction('removeMfa');

        $actions
            ->add(Crud::PAGE_INDEX, $importAction)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, $removeMfaAction)
            ->setPermissions([
                Action::INDEX => 'ROLE_MANAGE_USER_INDEX',
                Action::DETAIL => 'ROLE_MANAGE_USER_DETAIL',
                Action::NEW => 'ROLE_MANAGE_USER_NEW',
                Action::EDIT => 'ROLE_MANAGE_USER_EDIT',
                Action::DELETE => 'ROLE_MANAGE_USER_DELETE',
            ]);

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield EmailField::new('email', 'crud.users.grid.column_label.email');
        yield TextField::new('firstName', 'crud.users.grid.column_label.first_name')
            ->hideOnIndex();
        yield TextField::new('lastName', 'crud.users.grid.column_label.last_name')
            ->hideOnIndex();
        yield TextField::new('fullName', 'crud.users.grid.column_label.full_name')
            ->hideOnForm();
        yield TextField::new('plainPassword', 'crud.users.grid.column_label.password')
            ->setFormTypeOption('required', true)
            ->hideOnIndex()
            ->hideOnDetail()
            ->onlyWhenCreating();
        yield BooleanField::new('enabled', 'crud.users.grid.column_label.enabled');
        yield DateTimeField::new('lastLogin', 'crud.users.grid.column_label.last_login')
            ->hideOnIndex()
            ->hideOnForm()
            ->setFormat('dd/MM/Y HH:mm');
        yield TextField::new('updatePasswordToken', 'crud.users.grid.column_label.update_password_token')
            ->hideOnIndex()
            ->hideOnForm();
        yield DateField::new('expiresAt', 'crud.users.grid.column_label.expires_at')
            ->setFormat('dd/MM/Y')
            ->hideOnIndex();
        yield ChoiceField::new('roles', 'crud.users.grid.column_label.roles')
            ->setChoices($this->roleProvider->getRolesListAsOptionArray())
            ->setFormTypeOption('multiple', true)
            ->setFormTypeOption('required', false)
            ->formatValue(function($v) {
               $v = array_map('trim', $v);
                $res = '<span></span>';
                if ($v) {
                    $roles_list = array_flip($this->roleProvider->getRolesListAsOptionArray());
                    $res = implode("\n", array_map(function($r) use($roles_list) {
                        return isset($roles_list[$r]) ? '<span class="role">' . $roles_list[$r] . '</span>' : '<span></span>';
                    }, $v));
                }
                return $res;
            });
        yield TextField::new('mobilePhone', 'crud.users.grid.column_label.mobile_phone')
            ->hideOnIndex();
        yield TextField::new('fiscalId', 'crud.users.grid.column_label.fiscal_id')
            ->hideOnIndex();
        yield DateField::new('birth', 'crud.users.grid.column_label.birth')
            ->setFormat('dd/MM/Y')
            ->hideOnIndex();
        yield TextField::new('birthCountry', 'crud.users.grid.column_label.birth_country')
            ->hideOnIndex();
        yield TextField::new('birthRegion', 'crud.users.grid.column_label.birth_region')
            ->hideOnIndex();
        yield TextField::new('birthProvince', 'crud.users.grid.column_label.birth_province')
            ->hideOnIndex();
        yield TextField::new('birthCity', 'crud.users.grid.column_label.birth_city')
            ->hideOnIndex();
        yield AssociationField::new('confidi', 'crud.users.grid.column_label.confidi');
    }

    public function import(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $form = $this->createFormBuilder(null, ['attr' => ['id' => 'csv-import-users']])
            ->add('csvFile', FileType::class, [
                'label' => 'crud.users.form.import_csv.csv_file'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'crud.users.form.import_csv.submit',
                'attr' => [
                    'disabled' => true
                ]
            ])
            ->getForm();

        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();

            if ($csvFile) {
                $originalFilename = pathinfo($csvFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $csvFile->guessClientExtension();

                try {
                    $csvFile->move(
                        '../var/tmp',
                        $newFilename
                    );
                    $importReport = $this->csvImporter->importCsv('../var/tmp/' . $newFilename);
                    $this->addFlash('success', $this->translator->trans('crud.users.flash_messages.csv_import.success', $importReport));
                    $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
                    $url = $adminUrlGenerator
                        ->setController(UserCrudController::class)
                        ->setAction('import')
                        ->generateUrl();
                    return $this->redirect($url);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
            }
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => 'Import',
            'templatePath' => '@EasyAdmin/user/import.html.twig',
            'form' => $form->createView()
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function removeMfa(AdminContext $context): Response
    {
        $entity = $context->getEntity()->getInstance();
        $entity->setGoogleAuthenticatorSecret(null);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->addFlash('success', 'MFA Rimosso con successo!');
        return $this->redirect($this->adminUrlGenerator
            ->setController(UserCrudController::class)
            ->setAction(Action::EDIT)->generateUrl());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route(path="/users/validate-csv", name="users_validate_csv")
     */
    public function validateCsv(
        Request $request
    ): Response {
        $form = $this->createFormBuilder(null, ['attr' => ['id' => 'csv-import-users']])
            ->add('csvFile', FileType::class, [
                'label' => 'crud.users.form.import_csv.csv_file'
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $form->get('csvFile')->getData();

            if ($csvFile) {
                $originalFilename = pathinfo($csvFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $csvFile->guessClientExtension();

                try {
                    $csvFile->move(
                        '../var/tmp',
                        $newFilename
                    );
                    $this->csvImporter->validateCsv('../var/tmp/' . $newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
            }
        }

        $response = new JsonResponse();

        $response->setData([
            'errors' => $this->csvImporter->getErrors()
        ]);

        return $response;
    }

    /**
     * @return Response
     *
     * @Route(path="/users/download-csv-template", name="user_download_csv_template", )
     */
    public function downloadCsvTemplate(): Response
    {
        $response = new Response($this->csvImporter->getTemplateContent());

        $response->headers->set('Content-Type', 'text/csv');
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'import_users.csv');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->notLike('entity.roles', ':super_admin_role')
                )
                ->setParameter('super_admin_role', '%ROLE_SUPER_ADMIN%');
        }
        return $queryBuilder;
    }
}

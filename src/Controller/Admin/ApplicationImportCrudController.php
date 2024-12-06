<?php

namespace App\Controller\Admin;

use App\Entity\Application;
use App\Entity\ApplicationImport;
use App\Entity\ApplicationImportTemplate;
use App\Entity\Confidi;
use App\Entity\User;
use App\Form\CustomVichFileType;
//use App\Form\Extension\Type\EntityHiddenType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ApplicationImportCrudController extends AbstractCrudController
{
    const ACTION_DOWNLOAD = 'download';
    const ACTION_DOWNLOAD_TEMPLATE = 'downloadTemplate';

    /** @var FilesystemMap */
    private $filesystemMap;

    /**
     * ApplicationImportCrudController constructor.
     * @param FilesystemMap $filesystemMap
     */
    public function __construct(
        FilesystemMap $filesystemMap
    ) {
        $this->filesystemMap = $filesystemMap;
    }

    public static function getEntityFqcn(): string
    {
        return ApplicationImport::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('crud.application_import.singular')
            ->setEntityLabelInPlural('crud.application_import.plural')
            ->overrideTemplate('crud/new', 'bundles/EasyAdminBundle/application-import/import.html.twig')
            ->setFormThemes(['form/form_theme.html.twig'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $downloadAction = Action::new(self::ACTION_DOWNLOAD, 'crud.application_import.actions.download', 'fas fa-download')
            ->linkToCrudAction('download');

        $downloadTemplateAction = Action::new(self::ACTION_DOWNLOAD_TEMPLATE, 'crud.application_import.actions.download_template', 'fas fa-download')
            ->linkToCrudAction('downloadTemplate')
            ->setCssClass('btn btn-default')
            ->createAsGlobalAction();

        $checkImportStatus = static function(ApplicationImport $entity){
            return in_array($entity->getStatus(), [
                ApplicationImport::STATUS_FAILED,
                ApplicationImport::STATUS_ACQUIRED,
                ApplicationImport::STATUS_VALIDATION_SUCCEEDED,
                ApplicationImport::STATUS_VALIDATION_FAILED
            ]);
        };

        return $actions
            ->add(Crud::PAGE_INDEX, $downloadAction)
            ->add(Crud::PAGE_INDEX, $downloadTemplateAction)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $downloadAction)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_NEW, $downloadTemplateAction)
            ->update(Crud::PAGE_INDEX, Action::NEW, function(Action $action){
                return $action->setLabel('crud.application_import_template.actions.new');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function(Action $action) use ($checkImportStatus) {
                return $action->displayIf($checkImportStatus);
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function(Action $action) use ($checkImportStatus) {
                return $action->displayIf($checkImportStatus);
            })
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->setPermissions([
                Action::INDEX => 'ROLE_APPLICATION_IMPORT_INDEX',
                Action::DETAIL => 'ROLE_APPLICATION_IMPORT_DETAIL',
                Action::NEW => 'ROLE_APPLICATION_IMPORT_NEW',
                Action::EDIT => 'ROLE_APPLICATION_IMPORT_EDIT',
                Action::DELETE => 'ROLE_APPLICATION_IMPORT_DELETE',
                self::ACTION_DOWNLOAD => 'ROLE_APPLICATION_IMPORT_DOWNLOAD'
            ])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('filenameFile', 'crud.application_import.grid.column_label.filename_file')
                ->setFormType(CustomVichFileType::class)
                ->setFormTypeOption('required', true)
                ->setFormTypeOption('constraints', [])
                ->setFormTypeOption('attr', [
                    'accept' => implode(',', [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        '.xlsx'
                    ])
                ])
                ->hideOnIndex()
                ->hideOnDetail();
        yield TextField::new('filename', 'crud.application_import.grid.column_label.filename')
                ->hideOnForm();
        $isConfidiUser = ($user = $this->getUser()) &&
            $user instanceof User &&
            $user->getConfidi();
        $confidiField = AssociationField::new('confidi', 'crud.application_import.grid.column_label.confidi')
            ->setCssClass($isConfidiUser ? 'field-association hidden' : 'field-association')
            ->setFormTypeOption('attr', [
                'readonly' => $isConfidiUser
            ])
            ->setRequired(true)
            ->setFormTypeOption('disabled', $isConfidiUser);
        if ($isConfidiUser) {
            $confidiField->hideOnIndex();
        }
        yield $confidiField;
        yield DateTimeField::new('createdAt', 'crud.application_import.grid.column_label.created_at')
            ->setFormat('dd/MM/Y HH:mm')
            ->hideOnForm();
        yield ChoiceField::new('status', 'crud.application_import.grid.column_label.status')
                ->hideOnForm()
                ->setChoices(ApplicationImport::getStatusChoices());
        yield AssociationField::new('createdBy', 'crud.application_import.grid.column_label.created_by')
                ->onlyOnDetail();
        yield AssociationField::new('template')
                ->hideOnForm();
        yield ArrayField::new('errors', 'crud.application_import.grid.column_label.errors')
                ->formatValue(function($errors){
                    return array_map(function($err){
                        return $err['message'];
                    }, $errors);
                })
                ->onlyOnDetail();
        yield ArrayField::new('applications', 'crud.application_import.grid.column_label.applications')
                ->onlyOnDetail();
    }

    public function createEntity(string $entityFqcn)
    {
        $entity = parent::createEntity($entityFqcn);
        if (($user = $this->getUser()) &&
            $user instanceof User &&
            $user->getConfidi()) {
            $entity->setConfidi($user->getConfidi());
        }

        return $entity;
    }

    public function download(AdminContext $context)
    {
        $entity = $context->getEntity()->getInstance();

        $fs = $this->filesystemMap->get('application_import');

        $response = new Response($fs->read($entity->getFilename()));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $entity->getFilename());
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    public function downloadTemplate(
        ManagerRegistry $managerRegistry
    ): Response
    {
        $activeTemplate = $managerRegistry->getRepository(ApplicationImportTemplate::class)->findOneBy([
            'active' => true
        ]);

        if (!$activeTemplate) {
            throw new NotFoundHttpException('Template not found');
        }

        $fs = $this->filesystemMap->get('application_import_template');

        $response = new Response($fs->read($activeTemplate->getFilename()));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $activeTemplate->getFilename());
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
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
}

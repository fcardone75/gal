<?php

namespace App\Controller\Admin;

use App\Entity\ApplicationImportTemplate;
use App\Form\CustomVichFileType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ApplicationImportTemplateCrudController extends AbstractCrudController
{
    const ACTION_DOWNLOAD = 'download';

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
        return ApplicationImportTemplate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('crud.application_import_template.singular')
            ->setEntityLabelInPlural('crud.application_import_template.plural')
            ->overrideTemplate('crud/new', 'bundles/EasyAdminBundle/application-import-template/import.html.twig')
            ->setFormThemes(['form/form_theme.html.twig'])
            ->setDefaultSort(['active' => 'DESC', 'id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $downloadAction = Action::new(self::ACTION_DOWNLOAD, 'crud.application_import_template.actions.download', 'fas fa-download')
            ->linkToCrudAction('download');

        return $actions
            ->add(Crud::PAGE_INDEX, $downloadAction)
            ->add(Crud::PAGE_DETAIL, $downloadAction)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
//            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->disable(Action::EDIT, Action::DELETE)
            ->setPermissions([
                Action::INDEX => 'ROLE_APPLICATION_IMPORT_TEMPLATE_INDEX',
                Action::DETAIL => 'ROLE_APPLICATION_IMPORT_TEMPLATE_DETAIL',
                Action::NEW => 'ROLE_APPLICATION_IMPORT_TEMPLATE_NEW',
                Action::EDIT => 'ROLE_APPLICATION_IMPORT_TEMPLATE_EDIT',
                Action::DELETE => 'ROLE_APPLICATION_IMPORT_TEMPLATE_EDIT',
                self::ACTION_DOWNLOAD => 'ROLE_APPLICATION_IMPORT_TEMPLATE_DOWNLOAD',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new('filenameFile', 'crud.application_import_template.grid.column_label.filename_file')
                ->setFormType(CustomVichFileType::class)
                ->setFormTypeOption('required', true)
                ->setFormTypeOption('attr', [
                    'accept' => implode(',', [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ])
                ])
                ->hideOnIndex()
                ->hideOnDetail()
                ->onlyWhenCreating(),
            TextField::new('filename', 'crud.application_import_template.grid.column_label.filename')
                ->hideOnForm(),
            TextField::new('revision', 'crud.application_import_template.grid.column_label.revision')
                ->hideOnForm(),
            DateTimeField::new('createdAt', 'crud.application_import_template.grid.column_label.created_at')
                ->hideOnForm()
                ->hideOnIndex(),
            AssociationField::new('createdBy', 'crud.application_import_template.grid.column_label.created_by')
                ->hideOnForm()
                ->hideOnIndex(),
            BooleanField::new('active', 'crud.application_import_template.grid.column_label.active')
                ->hideOnForm()
                ->setDisabled()
        ];
    }

    public function download(AdminContext $context)
    {
        $entity = $context->getEntity()->getInstance();

        $fs = $this->filesystemMap->get('application_import_template');

        $response = new Response($fs->read($entity->getFilename()));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $entity->getFilename());
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}

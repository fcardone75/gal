<?php

namespace App\Controller\Admin;

use App\Entity\ApplicationGroup;
use App\Entity\User;
use App\Form\CustomVichFileType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Dompdf\Dompdf;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ApplicationGroupCrudController extends AbstractCrudController
{
    const ACTION_DOWNLOAD_PDF = 'downloadPdf';
    const ACTION_EDIT_WITH_DIFFERENT_LABEL = 'editWithDifferentLabel';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FilesystemMap
     */
    private $filesystemMap;

    /**
     * @var AdminUrlGenerator
     */
    private $adminUrlGenerator;

    public function __construct(
        TranslatorInterface $translator,
        FilesystemMap $filesystemMap,
        AdminUrlGenerator $adminUrlGenerator
    ) {
        $this->translator = $translator;
        $this->filesystemMap = $filesystemMap;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return ApplicationGroup::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('crud.application_group.plural')
            ->setEntityLabelInSingular('crud.application_group.singular')
            ->overrideTemplate('crud/edit', 'bundles/EasyAdminBundle/application-group/crud/edit.html.twig')
            ->setDefaultSort(['id' => 'DESC'])
            ->setFormThemes(['form/form_theme.html.twig'])
            ->setPageTitle(Crud::PAGE_EDIT, function (ApplicationGroup $a) {
                return
                    $this->translator->trans(
//                        $a->getStatus() !== ApplicationGroup::STATUS_REGISTERED ? 'page_title.edit' : 'page_title.detail',
                        $a->getStatus() === ApplicationGroup::STATUS_NEW ? 'page_title.edit' : 'page_title.detail',
                        [],
                        'EasyAdminBundle'
                    );
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')
                ->setChoices(
                    [
                        $this->translator->trans(ApplicationGroup::STATUS_NEW, [], 'application_group_status') => ApplicationGroup::STATUS_NEW,
                        $this->translator->trans(ApplicationGroup::STATUS_REGISTERED, [], 'application_group_status') => ApplicationGroup::STATUS_REGISTERED,
                        $this->translator->trans(ApplicationGroup::STATUS_SENT_TO_NSIA, [], 'application_group_status') => ApplicationGroup::STATUS_SENT_TO_NSIA,
                    ]
                )
                ->canSelectMultiple()
            )
            ->add(
                DateTimeFilter::new('protocolDate')
                    ->setLabel($this->translator->trans('crud.application_group.grid.column_label.protocol_date'))
                    // WARNING: do not remove/change the following line, since it disables datetime-local type rendering on html input
                    ->setFormTypeOptions(['value_type' => DateType::class])
            );
    }


    public function configureActions(Actions $actions): Actions
    {
        $downloadPdfAction = Action::new(
            self::ACTION_DOWNLOAD_PDF,
            'crud.application_group.actions.download_pdf',
            'fas fa-file-pdf'
            )
            ->linkToCrudAction('downloadPdf')
            ->setCssClass('btn btn-default')
            ->displayIf(function (ApplicationGroup $a){
//                return $a->getApplications()->count() > 0 && $a->getStatus() !== ApplicationGroup::STATUS_REGISTERED;
                return $a->getApplications()->count() > 0 && $a->getStatus() === ApplicationGroup::STATUS_NEW;
            });

        $editWithDifferentLabelAction = Action::new(
            self::ACTION_EDIT_WITH_DIFFERENT_LABEL,
            'crud.application_group.actions.edit_with_different_label'
            )
            ->linkToCrudAction(Action::EDIT)
            ->displayIf(function (ApplicationGroup $a){
//                return $a->getStatus() === ApplicationGroup::STATUS_REGISTERED;
                return $a->getStatus() !== ApplicationGroup::STATUS_NEW;
            });

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->add(Crud::PAGE_EDIT, $downloadPdfAction)
            ->update(Crud::PAGE_EDIT, Action::DELETE, function(Action $action){
                return $action
                    ->displayIf(function(ApplicationGroup $a){
//                        return $a->getStatus() !== ApplicationGroup::STATUS_REGISTERED;
                        return $a->getStatus() === ApplicationGroup::STATUS_NEW;
                    });
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function(Action $action){
                return $action
                    ->displayIf(function(ApplicationGroup $a){
//                        return $a->getStatus() !== ApplicationGroup::STATUS_REGISTERED;
                        return $a->getStatus() === ApplicationGroup::STATUS_NEW;
                    });
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function(Action $action){
                return $action
                    ->displayIf(function(ApplicationGroup $a){
//                        return $a->getStatus() !== ApplicationGroup::STATUS_REGISTERED;
                        return $a->getStatus() === ApplicationGroup::STATUS_NEW;
                    });
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action
                    ->displayIf(function(ApplicationGroup $a){
//                        return $a->getStatus() !== ApplicationGroup::STATUS_REGISTERED;
                        return $a->getStatus() === ApplicationGroup::STATUS_NEW;
                    });
            })
            ->add(Crud::PAGE_INDEX, $editWithDifferentLabelAction)
            ->setPermissions([
                Action::INDEX => 'ROLE_APPLICATION_GROUP_INDEX',
                Action::DETAIL => 'ROLE_APPLICATION_GROUP_DETAIL',
                Action::NEW => 'ROLE_APPLICATION_GROUP_NEW',
                Action::EDIT => 'ROLE_APPLICATION_GROUP_EDIT',
                Action::DELETE => 'ROLE_APPLICATION_GROUP_DELETE',
                self::ACTION_EDIT_WITH_DIFFERENT_LABEL => 'ROLE_APPLICATION_GROUP_EDIT',
            ])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();
        if($pageName !== Action::NEW) {
            yield TextField::new('protocolNumber', 'crud.application_group.grid.column_label.protocol_number')
                ->setFormTypeOption('attr', [
                    'readonly' => 'readonly'
                ])
//            ->onlyWhenUpdating()
            ;
        }
        $isConfidiUser = ($user = $this->getUser()) &&
            $user instanceof User &&
            $user->getConfidi();
        $confidiField = AssociationField::new('confidi', 'crud.application_group.grid.column_label.confidi')
            ->setFormTypeOption('attr', [
                'readonly' => $isConfidiUser
            ])
            ->setFormTypeOption('disabled', $isConfidiUser)
//            ->onlyWhenCreating()
        ;
        if ($isConfidiUser) {
            $confidiField
                ->hideOnIndex()
                ->hideOnForm();
        }
        if($pageName === Action::EDIT) {
            $confidiField
                ->hideOnForm();
        }
        yield $confidiField;
        if($pageName !== Action::NEW) {
            yield DateTimeField::new('protocolDate', 'crud.application_group.grid.column_label.protocol_date')
                ->setFormat('dd/MM/Y HH:mm')
                ->setFormTypeOption('attr', [
                    'readonly' => 'readonly'
                ])
            ;
        }
        yield TextField::new('status', 'crud.application_group.grid.column_label.status')
            ->formatValue(function($value){
                return $this->translator->trans($value, [], 'application_group_status');
            })
            ->hideOnForm();
        yield Field::new('filenameFile', 'crud.application_group.grid.column_label.filename_file')
            ->setFormType(CustomVichFileType::class)
            ->setFormTypeOption('allow_delete', false)
            ->setFormTypeOption('disable_upload', function (ApplicationGroup $applicationGroup) {
                return (bool) $applicationGroup->getProtocolNumber();
            })
            ->setFormTypeOption('download_uri', function (ApplicationGroup $applicationGroup) {
                if($applicationGroup->getId() && $applicationGroup->getFilename()) {
                    return $this->generateUrl('app_admin_applicationgroupcrud_download', [
                        'id' => $applicationGroup->getId(),
                        'fileName' => $applicationGroup->getOriginalFileName() ?: $applicationGroup->getFilename()
                    ]);
                }
                return null;
            })
            ->setFormTypeOption('download_label', false)
            ->setFormTypeOption('attr', [
                'accept' => implode(',', [
                    'application/pkcs7-signature',
                    'application/x-pkcs7-signature',
                    'application/pkcs7-mime',
                    'application/x-pkcs7-mime',
                    'application/x-pkcs7-certreqresp'
                ])
            ])
            ->hideOnIndex()
            ->hideOnDetail()
            ->onlyWhenUpdating();
        yield AssociationField::new('applications', 'crud.application_group.grid.column_label.applications')
            ->setFormTypeOption('multiple', true)
            ->setFormTypeOption('row_attr.class','hidden')
            ->setFormTypeOption('by_reference', false)
            //->setCssClass('hidden')
        ;
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

    // NB IMPORTANT: $fileName is used to print filename in download link label(VichFileType), don't delete this param
    /**
     * @Route("application-group/{id}/download-p7m/{fileName}", methods={"GET"})
     */
    public function download(
        ManagerRegistry $managerRegistry,
        $id,
        $fileName
    ): Response
    {
        $em = $managerRegistry->getManager();
        $entity = $em->getRepository(ApplicationGroup::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('ApplicationGroup not found');
        }

        $fs = $this->filesystemMap->get('application_group');

        $response = new Response($fs->read($entity->getFileName()));
        $response->headers->set('Content-Type', 'application/octet-stream');
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $entity->getFileName());
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    public function downloadPdf(AdminContext $adminContext)
    {
        /** @var ApplicationGroup $applicationGroup */
        $applicationGroup = $adminContext->getEntity()->getInstance();

        $dompdf = new Dompdf();

        $dompdf->setPaper('A4');

        $dompdf->loadHtml($this->renderView('application_group/pdf.html.twig', ['entity' => $applicationGroup]));
        $dompdf->render();

        $filename = implode('_', [
            'CONFIDI',
            $applicationGroup->getConfidi()->getNsiaCode(),
            $applicationGroup->getId()
        ]) . '.pdf';

        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
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

        if ($this->isGranted('ROLE_APPLICATION_GROUP_INDEX_NOT_NEW')) {
            $qb
                ->andWhere(
                    $qb->expr()->neq('entity.status', ':status')
                )
                ->setParameter('status', ApplicationGroup::STATUS_NEW);
        }



        return $qb;
    }
}

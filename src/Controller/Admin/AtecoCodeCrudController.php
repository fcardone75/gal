<?php

namespace App\Controller\Admin;

use App\Entity\AtecoCode;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AtecoCodeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AtecoCode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('crud.ateco_codes.singular')
            ->setEntityLabelInPlural('crud.ateco_codes.plural');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->setPermissions([
                Action::INDEX => 'ROLE_ATECO_CODE_INDEX',
                Action::DETAIL => 'ROLE_ATECO_CODE_DETAIL',
                Action::NEW => 'ROLE_ATECO_CODE_NEW',
                Action::EDIT => 'ROLE_ATECO_CODE_EDIT',
                Action::DELETE => 'ROLE_ATECO_CODE_DELETE',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'crud.ateco_codes.grid.column_label.id')->hideOnForm();
        yield TextField::new('code', 'crud.ateco_codes.grid.column_label.code')->setMaxLength(10);
        yield TextField::new('codeWithoutDots', 'crud.ateco_codes.grid.column_label.code_without_dots')->setMaxLength(10);
        yield TextField::new('description', 'crud.ateco_codes.grid.column_label.description');
        yield TextField::new('section', 'crud.ateco_codes.grid.column_label.section')->setMaxLength(1);
    }
}

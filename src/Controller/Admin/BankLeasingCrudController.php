<?php

namespace App\Controller\Admin;

use App\Entity\BankLeasing;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BankLeasingCrudController extends AbstractCrudController
{
    use ReferenceEntityCrudTrait;

    public static function getEntityFqcn(): string
    {
        return BankLeasing::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('crud.banks_leasing.singular')
            ->setEntityLabelInPlural('crud.banks_leasing.plural');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->setPermissions([
                Action::INDEX => 'ROLE_BANK_LEASING_INDEX',
                Action::DETAIL => 'ROLE_BANK_LEASING_DETAIL',
                Action::NEW => 'ROLE_BANK_LEASING_NEW',
                Action::EDIT => 'ROLE_BANK_LEASING_EDIT',
                Action::DELETE => 'ROLE_BANK_LEASING_DELETE',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'crud.banks_leasing.grid.column_label.id')->hideOnForm();
        yield TextField::new('name', 'crud.banks_leasing.grid.column_label.name');
        yield TextField::new('code', 'crud.banks_leasing.grid.column_label.code')->setMaxLength(8);
        yield AssociationField::new('template', 'crud.banks_leasing.grid.column_label.template');
    }
}

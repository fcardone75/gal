<?php

namespace App\Controller\Admin;

use App\Entity\Bank;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

class BankCrudController extends AbstractCrudController
{
    use ReferenceEntityCrudTrait;

    public static function getEntityFqcn(): string
    {
        return Bank::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('crud.banks.singular')
            ->setEntityLabelInPlural('crud.banks.plural');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->setPermissions([
                Action::INDEX => 'ROLE_BANK_INDEX',
                Action::DETAIL => 'ROLE_BANK_DETAIL',
                Action::NEW => 'ROLE_BANK_NEW',
                Action::EDIT => 'ROLE_BANK_EDIT',
                Action::DELETE => 'ROLE_BANK_DELETE',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'crud.banks.grid.column_label.id')->hideOnForm();
        yield TextField::new('name', 'crud.banks.grid.column_label.name');
        yield TextField::new('code', 'crud.banks.grid.column_label.code')->setMaxLength(8);
        yield AssociationField::new('template', 'crud.banks.grid.column_label.template');
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\Confidi;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ConfidiCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Confidi::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('crud.confidi.singular')
            ->setEntityLabelInPlural('crud.confidi.plural');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->setPermissions([
                Action::INDEX => 'ROLE_CONFIDI_INDEX',
                Action::DETAIL => 'ROLE_CONFIDI_DETAIL',
                Action::NEW => 'ROLE_CONFIDI_NEW',
                Action::EDIT => 'ROLE_CONFIDI_EDIT',
                Action::DELETE => 'ROLE_CONFIDI_DELETE',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('businessName', 'crud.confidi.grid.column_label.business_name')
            ->setRequired(true);
        yield TextField::new('legalRepresentative', 'crud.confidi.grid.column_label.legal_representative')
            ->setRequired(true);
        yield TextField::new('iban', 'crud.confidi.grid.column_label.iban');
        yield TextField::new('fiscalCode', 'crud.confidi.grid.column_label.fiscal_code');
        yield TextField::new('nsiaCode', 'crud.confidi.grid.column_label.nsia_code')
            ->setRequired(true);
    }
}

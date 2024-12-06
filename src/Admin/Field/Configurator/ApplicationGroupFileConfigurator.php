<?php


namespace App\Admin\Field\Configurator;


use App\Entity\ApplicationGroup;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Vich\UploaderBundle\Form\Type\VichFileType;

final class ApplicationGroupFileConfigurator implements FieldConfiguratorInterface
{

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return Field::class === $field->getFieldFqcn() && $field->getFormType() === VichFileType::class && $entityDto->getFqcn() === ApplicationGroup::class;
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $entity = $entityDto->getInstance();

        if ($entity instanceof ApplicationGroup &&
            ($this->applicationGroupIsRegistered($entity) || $this->applicationGroupIsEmpty($entity))) {
            $field->setFormTypeOption('required', false);
            if ($this->applicationGroupIsEmpty($entity)) {
                $field->setFormTypeOption('row_attr.class', 'hidden');
            }
            if ($this->applicationGroupIsRegistered($entity)) {
                $field->setFormTypeOption('attr.disabled', true);
                $field->setFormTypeOption('allow_delete', false);
            }
        }
    }

    protected function applicationGroupIsRegistered(ApplicationGroup $applicationGroup): bool
    {
        return $applicationGroup->getStatus() === ApplicationGroup::STATUS_REGISTERED &&
            $applicationGroup->getFilename();
    }

    protected function applicationGroupIsEmpty(ApplicationGroup $applicationGroup): bool
    {
        return $applicationGroup->getApplications()->count() === 0;
    }
}

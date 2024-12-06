<?php

namespace App\Form;

use App\Controller\Admin\ApplicationCrudController;
use App\Entity\AdditionalContribution;
use App\Entity\Application;
use App\Service\ApplicationFormManager;
use Doctrine\Common\Collections\ArrayCollection;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationAdditionalContributionCollectionType extends AbstractType
{
    /**
     * @var AdminUrlGenerator
     */
    private $adminUrlGenerator;

    /**
     * @var ApplicationFormManager
     */
    private $applicationFormManager;

    public function __construct(AdminUrlGenerator $adminUrlGenerator, ApplicationFormManager $applicationFormManager)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->applicationFormManager = $applicationFormManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Application $entity */
        $entity = $builder->getData();
        $builder
            ->setAction(
                $this->adminUrlGenerator
                    ->setController(ApplicationCrudController::class)
                    ->setAction('processApplicationAdditionalContributionCollectionForm')
                    ->setEntityId($entity->getId())
                    ->generateUrl()
            )
            ->add('additionalContributions', CollectionType::class, [
                    'entry_type' => AdditionalContributionType::class,
                    'allow_add' => $this->applicationFormManager->canEditForm($entity, ApplicationFormManager::APPLICATION_ADDITIONAL_CONTRIBUTIONS_FORM),
                    'allow_delete' => true,
                    'by_reference' => false,
                    'entry_options' => array('label' => false),
                    'label' => false
                ]
            )
            ->add(
                'submit', SubmitType::class,
                [
                    'label' => 'Salva',
                    'attr' => array('class' => 'btn btn-primary pull-right'),
                    'disabled' => !$this->applicationFormManager->canEditForm($entity, ApplicationFormManager::APPLICATION_ADDITIONAL_CONTRIBUTIONS_FORM)
                ]
            )
//            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
//                /** @var Application $entity */
//                $entity = $event->getData();
//            })
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        usort($view['additionalContributions']->children, function (FormView $a, FormView $b) {
            /** @var AdditionalContribution $objectA */
            $objectA = $a->vars['data'];
            /** @var AdditionalContribution $objectB */
            $objectB = $b->vars['data'];

            $posA = $objectA->getFormOrderBy();
            $posB = $objectB->getFormOrderBy();

            if ($posA == $posB) {
                return 0;
            }

            return ($posA < $posB) ? -1 : 1;
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Application',
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

}

<?php

namespace App\Form;

use App\Controller\Admin\ApplicationCrudController;
use App\Entity\Application;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationAttachmentCollectionType extends AbstractType
{
    /**
     * @var AdminUrlGenerator
     */
    private $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Application $entity */
        $entity = $builder->getData();
        $builder
            ->setAction(
                $this->adminUrlGenerator
                    ->setController(ApplicationCrudController::class)
                    ->setAction('processApplicationAttachmentCollectionForm')
                    ->setEntityId($entity->getId())
                    ->generateUrl()
            )
            ->add('applicationAttachments', CollectionType::class, [
                    'entry_type' => ApplicationAttachmentType::class,
                    'allow_add' => true,
                    'allow_delete' => false,
                    'by_reference' => false,
                    'entry_options' => array('label' => false),
                    'label' => false,
                    'delete_empty' => true
                ]
            )
            ->add(
                'submit', SubmitType::class,
                [
                    'label' => 'Salva',
                    'attr' => array('class' => 'btn btn-primary pull-right btn-save-application-attachments')
                ]
            )
        ;

// mantis http://mantis.synesthesia.it/view.php?id=9112
//        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event){
//            /** @var Application $application */
//            $application = $event->getData();
//            if ($application && $application->getStatus() === Application::STATUS_REGISTERED) {
//                $form = $event->getForm();
//                $form
//                    ->add('applicationAttachments', CollectionType::class, [
//                            'entry_type' => ApplicationAttachmentType::class,
//                            'allow_add' => false,
//                            'allow_delete' => false,
//                            'by_reference' => false,
//                            'entry_options' => array('label' => false),
//                            'label' => false,
//                            'delete_empty' => true
//                        ]
//                    )
//                    ->add('submit', SubmitType::class, [
//                        'label' => 'Salva',
//                        'attr' => [
//                            'class' => 'btn btn-primary pull-right btn-save-application-attachments hidden',
//                            'disabled' => true
//                        ]
//                ]);
//            }
//        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Application'
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

}

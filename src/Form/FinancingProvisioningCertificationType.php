<?php

namespace App\Form;

use App\Controller\Admin\ApplicationCrudController;
use App\Controller\Admin\FinancingProvisioningCertificationCrudController;
use App\Entity\Application;
use App\Entity\FinancingProvisioningCertification;
use App\Entity\Periodicity;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FinancingProvisioningCertificationType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var AdminUrlGenerator
     */
    private $adminUrlGenerator;

    public function __construct(EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var FinancingProvisioningCertification $entity */
        $entity = $builder->getData();
        $disableEdit = $entity->getStatus() !== FinancingProvisioningCertification::STATUS_PENDING;
        $builder
//            ->add('amount', NumberType::class, [
            ->add('amount', MoneyType::class, [
                'label' => 'Importo Finanziamento',
                'html5' => true,
                'required' => true,
                'attr' => [
                    'step' => '.01',
                    'min' => '.01',
                    'disabled' => $disableEdit
                ]])
            ->add('contractSignatureDate', DateType::class, [
                'label' => 'Data Firma Contratto',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'attr' => [
                    'disabled' => $disableEdit
                ]
            ])
            ->add('issueDate', DateType::class, [
                'label' => 'Data Erogazione',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'attr' => [
                    'disabled' => $disableEdit
                ]
            ])
            ->add('firstDepreciationDeadline', DateType::class, [
                'label' => 'Data Scadenza Prima Rata Ammortamento',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'attr' => [
                    'disabled' => $disableEdit
                ]
            ])
            ->add('lastDepreciationDeadline', DateType::class, [
                'label' => 'Data Scadenza Ultima Rata Ammortamento',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'attr' => [
                    'disabled' => $disableEdit,
                    'min' => 0
                ]
            ])
            ->add('preDepreciation', NumberType::class, [
                'label' => 'Eventuali rate di preammortamento',
                'html5' => true,
                'required' => true,
                'attr' => [
                    'disabled' => $disableEdit,
                    'min' => 0
                ]
            ])
//            ->add('installmentAmount', NumberType::class, [
            ->add('installmentAmount', MoneyType::class, [
                'label' => 'Importo Rata Ammortamento',
                'html5' => true,
                'attr' => [
                    'step' => '.01',
                    'min' => '.01',
                    'disabled' => $disableEdit
                ]])
            ->add('rateType', ChoiceType::class, [
                'label' => 'Tipologia Tasso',
                'required' => true,
                'choices' => [
                    'seleziona ...' => '',
                    'Fisso' => 'F',
                    'Variabile' => 'V',
                ],
                'attr' => [
                    'disabled' => $disableEdit
                ]
            ])
            ->add('rate', PercentType::class, [
                'label' => 'Tasso',
                'html5' => true,
                'required' => true,
                'scale' => 5,
                'type' => 'integer',
                'attr' => [
                    'step' => '.00001',
                    'min' => '.00001',
                    'disabled' => $disableEdit

                ]])
            ->add('taeg', PercentType::class, [
                'label' => 'Taeg',
                'html5' => true,
                'required' => true,
                'scale' => 5,
                'type' => 'integer',
                'attr' => [
                    'step' => '.00001',
                    'min' => '.00001',
                    'disabled' => $disableEdit
                ]])
            ->add('spread', PercentType::class, [
                'label' => 'Spread',
                'html5' => true,
                'required' => true,
                'scale' => 5,
                'type' => 'integer',
                'attr' => [
                    'step' => '.00001',
                    'min' => '.00001',
                    'disabled' => $disableEdit
                ]])
            ->add('application',EntityType::class, [
                'class' => Application::class,
                'required' => true,
                'attr' => [
                    'disabled' => $disableEdit
                ]] )
            ->add('periodicity')
            ->add('financingDuration', HiddenType::class, [
                'required' => true,
                'attr' => [
                    'disabled' => $disableEdit
                ]])
//            ->add('status')
//            ->add('createdAt')
//            ->add('updatedAt')
//            ->add('deletedAt')
//            ->add('createdBy')
//            ->add('updatedBy')
        ;
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event){
            /** @var FinancingProvisioningCertification|null $data */
            $data = $event->getData();
            $form = $event->getForm();
            if($data && ($application = $data->getApplication())){
                $applicationImportTemplate = $application->getApplicationImport()? $application->getApplicationImport()->getTemplate():null;
                $disableEdit = $data->getStatus() !== FinancingProvisioningCertification::STATUS_PENDING;
                if ($applicationImportTemplate){
                    $periodicityChoices = $this->entityManager->getRepository(Periodicity::class)->findBy(['template'=>$applicationImportTemplate->getId()]);
                    $choices = [];
                    foreach ($periodicityChoices as $periodicityChoice){
                        $choices[$periodicityChoice->getType()] = $periodicityChoice->getType();
                    }
                    $form->add('periodicity', ChoiceType::class, [
                        'label' => 'Periodicità Rate',
                        'choices' =>$choices,
                        'required' => true,
                        'attr' => [
                            'disabled' => $disableEdit
                        ]
                    ]);
                }
                $form->add('submit', SubmitType::class, [
                    'label' => 'Salva',
                    'attr' => [
                        'disabled' => $disableEdit,
                        'hidden' => $disableEdit
                    ]]);
            }

        }  );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FinancingProvisioningCertification::class,
        ]);
    }
}

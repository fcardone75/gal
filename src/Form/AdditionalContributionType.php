<?php

namespace App\Form;

use App\Entity\AdditionalContribution;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdditionalContributionType extends AbstractType
{
    private $labelMap = [
        AdditionalContribution::TYPE_ABB,
        AdditionalContribution::TYPE_CON,
        AdditionalContribution::TYPE_CFP,
    ];

    public function __construct(TranslatorInterface $translator)
    {
        $labelMap = [];
        foreach ($this->labelMap as $value) {
            $labelMap[$value] = $translator->trans($value);
        }
        $this->labelMap = $labelMap;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder){
            /** @var AdditionalContribution $entity */
            $entity = $event->getData();
            $form = $event->getForm();
            $form->add('type', HiddenType::class); // NB: don't delete this row
            if($entity) {
                if(!$entity->getId()) {
                    $form
                        ->add('type', CheckboxType::class, [
                                'label' => $this->labelMap[$entity->getType()],
                                'value' => $entity->getType(),
                                'required' => false,
                                'model_transformer' => new CallbackTransformer(
                                    function ($string) use ($entity) {
                                        return (bool)$entity->getId();
                                    },
                                    function ($bool) use ($entity) {
                                        return $entity->getType();
                                    }
                                )
                            ]
                        )
                    ;
                } else {
                    $form->add('typeNotMapped', CheckboxType::class, [
                        'label' => $this->labelMap[$entity->getType()],
                        'value' => $entity->getType(),
                        'data' => true,
                        'required' => false,
                        'disabled' => true,
                        'mapped' => false
                    ]);
                    if(
                        $entity->getType() ===  AdditionalContribution::TYPE_CON &&
                        $entity->getApplication()->getAdditionalContributionsOfType(
                            $entity->getApplication()->getAdditionalContributions(),
                            AdditionalContribution::TYPE_CFP
                        )->count() === 0
                    ) {
                        $form->add('typeNotMappedMissing', CheckboxType::class, [
                            'label' => $this->labelMap[AdditionalContribution::TYPE_CFP],
                            'value' => AdditionalContribution::TYPE_CFP,
                            'data' => false,
                            'required' => false,
                            'disabled' => true,
                            'mapped' => false
                        ]);
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\AdditionalContribution',
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

}

<?php

namespace App\Form;

use App\Entity\ApplicationMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationMessageType extends AbstractType
{

	/**
	 * ApplicationMessageType constructor.
	 */
	public function __construct()
	{

	}


	public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event) use($options) {
			/** @var ApplicationMessage $applicationMessage */
			$applicationMessage = $event->getData();
			$form = $event->getForm();
			$enabled = true;
            $app_admin_applicationcrud_postmessages = '/applications/'.$options['attr']['application_id'].'/messages';
            $app_admin_applicationcrud_patchmessages = '/applications/'.$options['attr']['application_id'].'/messages/%7Bid%7D';
            $applicationMessageForm_vars_value = $applicationMessage && !$applicationMessage->getPublished() ? $applicationMessage->getId() : null;

			$form
				->add('text', TextareaType::class, [
					'label' => '',
                    'attr' => [
                        'readonly' => !$enabled,
                        'data-app_admin_applicationcrud_postmessages' => $app_admin_applicationcrud_postmessages,
                        'data-app_admin_applicationcrud_patchmessages' => $app_admin_applicationcrud_patchmessages,
                        'data-applicationMessageForm_vars_value' => $applicationMessageForm_vars_value
                    ]
                ])
				->add('attachments', FileType::class, [
					'multiple' => true,
					'data_class' => null
				])
			;
		});
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\ApplicationMessage',
            'csrf_protection' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

}

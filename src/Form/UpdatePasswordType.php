<?php

namespace App\Form;


use App\Model\UserInterface;
use App\Validator\Constraints\UserPassword;use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdatePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'mapped' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'security.form.update_password.current_password'
                ],
                'row_attr' => [
                    'class' => 'form-widget'
                ],
                'constraints' => [
                    new NotBlank(),
                    new UserPassword()
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'security.form.update_password.new_password'
                    ],
                    'row_attr' => [
                        'class' => 'form-widget'
                    ]
                ],
                'second_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'security.form.update_password.new_password_confirmation'
                    ],
                    'row_attr' => [
                        'class' => 'form-widget'
                    ]
                ],
                'invalid_message' => 'password_mismatch'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'security.form.update_password.submit',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg btn-block'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => UserInterface::class,
            ]);
    }
}

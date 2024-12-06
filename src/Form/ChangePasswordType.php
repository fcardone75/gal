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

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'security.form.update_password.current_password',
                'constraints' => [
                    new NotBlank(),
                    new UserPassword()
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => array('label' => 'security.form.update_password.new_password'),
                'second_options' => array('label' => 'security.form.update_password.new_password_confirmation'),
                'invalid_message' => 'password_mismatch'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'security.form.update_password.submit'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => UserInterface::class,
                'validation_groups' => [
                    'Default',
                    'change_password'
                ]
            ]);
    }
}

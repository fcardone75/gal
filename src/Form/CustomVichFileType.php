<?php

namespace App\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomVichFileType extends \Vich\UploaderBundle\Form\Type\VichFileType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('disable_upload', false);

        $resolver->addAllowedTypes('disable_upload', ['bool', 'callable']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $object = $form->getParent()->getData();

        $view->vars['disable_upload'] = null;
        if ($object) {
            $view->vars['disable_upload'] = $this->resolveDisableUpload($options['disable_upload'], $object, $form);
        }
    }

    protected function resolveDisableUpload($disableUploadOption, $object, FormInterface $form)
    {
        if (\is_callable($disableUploadOption)) {
            return $disableUploadOption($object);
        }

        return $disableUploadOption;
    }
}

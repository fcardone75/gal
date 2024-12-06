<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\ApplicationAttachment;
use App\Service\FileTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ApplicationAttachmentType extends AbstractType
{
    use FileTrait;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface  $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('uploadFile', VichFileType::class, [
                'label' => 'File',
                'allow_delete' => false,
                'required' => false,
                'download_uri' => function (ApplicationAttachment $applicationAttachment) {
                    if ($applicationAttachment->getId() && $applicationAttachment->getFilename()) {
                        return $this->urlGenerator->generate('app_admin_applicationattachmentcrud_download', [
                            'id' => $applicationAttachment->getId(),
                            'fileName' => $applicationAttachment->getOriginalFileName() ?: $applicationAttachment->getFileName()
                        ]);
                    }
                    return null;
                },
                'download_label' => function (ApplicationAttachment $applicationAttachment) {
                    if ($applicationAttachment->getId() && $applicationAttachment->getFilename()) {
                        $fileSizeLabel = $this->getFormattedSizeUnits($applicationAttachment->getFileSize());
                        $fileUploadedAtLabel = $applicationAttachment->getFileUploadedAt() ?
                            $applicationAttachment->getFileUploadedAt()->format("d/m/Y H:i:s")
                            :
                            null;
                        return "({$fileSizeLabel} - {$fileUploadedAtLabel})";
                    }
                    return null;
                },
                /**
                 * This piece of code does not work as intended.
                 * When attempting to execute or apply it within a specific context or application,
                 * it fails to perform its expected function or produce the desired outcome.
                 *
                'attr' => [
                    'class' => 'application_attachments_vich_upload_file_onchange'
                ]
                 * */
            ])
            ->add('description', TextType::class, ['label' => 'Descrizione'])
        ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event){
            /** @var ApplicationAttachment $applicationAttachment */
            $applicationAttachment = $event->getData();
            if ($applicationAttachment && $applicationAttachment->getApplication() && $applicationAttachment->getApplication()->getStatus() === Application::STATUS_REGISTERED) {
                $event->getForm()
                    ->add('uploadFile', VichFileType::class, [
                        'label' => 'File',
                        'allow_delete' => false,
                        'allow_file_upload' => false,
                        'attr' => [
                            'disabled' => true
//                            'readonly' => true
                        ],
                        'required' => false,
                        'download_uri' => function (ApplicationAttachment $applicationAttachment) {
                            if ($applicationAttachment->getId() && $applicationAttachment->getFilename()) {
                                return $this->urlGenerator->generate('app_admin_applicationattachmentcrud_download', [
                                    'id' => $applicationAttachment->getId(),
                                    'fileName' => $applicationAttachment->getOriginalFileName() ?: $applicationAttachment->getFileName()
                                ]);
                            }
                            return null;
                        },
                        'download_label' => function (ApplicationAttachment $applicationAttachment) {
                            if ($applicationAttachment->getId() && $applicationAttachment->getFilename()) {
                                $fileSizeLabel = $this->getFormattedSizeUnits($applicationAttachment->getFileSize());
                                $fileUploadedAtLabel = $applicationAttachment->getFileUploadedAt() ?
                                    $applicationAttachment->getFileUploadedAt()->format("d/m/Y H:i:s")
                                    :
                                    null;
                                return "({$fileSizeLabel} - {$fileUploadedAtLabel})";
                            }
                            return null;
                        }
                    ])
                    ->add('description', TextType::class, [
                        'label' => 'Descrizione',
                        'attr' => [
//                            'disabled' => true
                            'readonly' => true
                        ]
                    ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\ApplicationAttachment'
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }

}

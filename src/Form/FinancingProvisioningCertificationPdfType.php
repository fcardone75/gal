<?php

namespace App\Form;

use App\Controller\Admin\FinancingProvisioningCertificationCrudController;
use App\Entity\FinancingProvisioningCertification;
use App\Service\FileTrait;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

class FinancingProvisioningCertificationPdfType extends AbstractType
{
    use FileTrait;

    /**
     * @var AdminUrlGenerator
     */
    private $adminUrlGenerator;
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    public function __construct(AdminUrlGenerator $adminUrlGenerator, UrlGeneratorInterface $router)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('filenameFile', VichFileType::class, [
                'label' => 'File firmato',
                'allow_delete' => false,
                'download_uri' => function (FinancingProvisioningCertification $financingProvisioning) {
                    if ($financingProvisioning->getId() && $financingProvisioning->getFilename()) {
                        return $this->router->generate('financing-provisioning-download-signed-pdf', [
                            'id' => $financingProvisioning->getId(),
                            'fileName' => $financingProvisioning->getOriginalFileName() ?: $financingProvisioning->getFileName()
                        ]);
                    }
                    return null;
                },
                'download_label' => function (FinancingProvisioningCertification $financingProvisioning) {
                    if ($financingProvisioning->getId() && $financingProvisioning->getFilename()) {
                        $fileSizeLabel = $this->getFormattedSizeUnits($financingProvisioning->getFileSize());
                        $fileUploadedAtLabel = $financingProvisioning->getFileUploadedAt() ?
                            $financingProvisioning->getFileUploadedAt()->format("d/m/Y H:i:s")
                            :
                            null;
                        return "({$fileSizeLabel} - {$fileUploadedAtLabel})";
                    }
                    return null;
                }
            ])
            ->add('submit', SubmitType::class , [
                'label' => 'Upload'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FinancingProvisioningCertification::class,
            'method' => Request::METHOD_POST,
        ]);
    }
}

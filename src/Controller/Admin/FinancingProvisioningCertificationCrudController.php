<?php

namespace App\Controller\Admin;

use App\Entity\Application;
use App\Entity\FinancingProvisioningCertification;
use App\Form\FinancingProvisioningCertificationType;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Dompdf\Dompdf;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class FinancingProvisioningCertificationCrudController extends AbstractCrudController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ApplicationRepository
     */
    private $applicationRepository;
    /**
     * @var FilesystemMap
     */
    private $filesystemMap;


    public function __construct(
        ApplicationRepository $applicationRepository,
        EntityManagerInterface $entityManager,
        FilesystemMap $filesystemMap
    )
    {
        $this->applicationRepository = $applicationRepository;
        $this->entityManager = $entityManager;
        $this->filesystemMap = $filesystemMap;
    }
    public static function getEntityFqcn(): string
    {
        return FinancingProvisioningCertification::class;
    }
    public function create(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator,
        ManagerRegistry $managerRegistry,
        Request $request
    ): Response
    {
        /** @var FinancingProvisioningCertification $entity */
        $entity = $this->createEntity($context->getEntity()->getFqcn());
        $form = $this->createForm(FinancingProvisioningCertificationType::class, $entity);
        $form->handleRequest($request);
        $url = $adminUrlGenerator
                ->setController(ApplicationCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($entity->getApplication()->getId())
                ->generateUrl()."#application_attachments_focus";
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $managerRegistry->getManager();
            $em->persist($entity );
            $em->flush();
        }
        return $this->redirect($url);
    }

    public function modify(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator,
        ManagerRegistry $managerRegistry,
        Request $request
    ): Response
    {
        /** @var FinancingProvisioningCertification $entity */
        $entity = $context->getEntity()->getInstance();
        $form = $this->createForm(FinancingProvisioningCertificationType::class, $entity);
        $form->handleRequest($request);
        $url = $adminUrlGenerator
                ->setController(ApplicationCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($entity->getApplication()->getId())
                ->generateUrl()."#application_attachments_focus";
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $managerRegistry->getManager();

            $em->flush();
        }
        return $this->redirect($url);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return Response
     * @Route("application/{id}/download-financing-provisioning-certification-pdf",methods={"GET"}, name="download-financing-provisioning-certification-pdf")
     */
    public function downloadPdf(Request $request, int $id): Response
    {
        /** @var Application $application */
        $application = $this->applicationRepository->find($id);
        /** @var FinancingProvisioningCertification $financingProvisioningCertification */
        $financingProvisioningCertification = $application->getFinancingProvisioningCertification();

        $dompdf = new Dompdf();

        $dompdf->setPaper('A4');

        $dompdf->loadHtml($this->renderView('financing_provisioning_certification/pdf.html.twig', ['entity' => $application , 'financingData' => $financingProvisioningCertification]));
        $dompdf->render();

        $filename = implode('_', [
                'Attestazione_Finanziamento',
                $application->getPracticeId(),
                $financingProvisioningCertification->getId()
            ]) . '.pdf';

        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $dispositionHeader);
        $financingProvisioningCertification->setStatus(FinancingProvisioningCertification::STATUS_DOWNLOADED);
        $this->entityManager->flush();
        return $response;
    }

    /**
     * @param Request $request
     * @param int $id
     * @return Response
     * @Route("application/{id}/reset-financing-provisioning-certification-pdf",methods={"GET"}, name="reset-financing-provisioning-certification-pdf")
     */
    public function resetPdf(Request $request, int $id)
    {
        /** @var Application $application */
        $application = $this->applicationRepository->find($id);
        /** @var FinancingProvisioningCertification $financingProvisioningCertification */
        $financingProvisioningCertification = $application->getFinancingProvisioningCertification();

        $financingProvisioningCertification->setStatus(FinancingProvisioningCertification::STATUS_PENDING);
        $this->entityManager->flush();

        $url = $this->get(AdminUrlGenerator::class)
                ->setController(ApplicationCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($application->getId())
                ->generateUrl();

        return $this->redirect($url);
    }


    // NB IMPORTANT: $fileName is used to print filename in download link label(VichFileType), don't delete this param
    /**
     * @Route("application/{id}/download-financing-provisioning-certification-pdf/signed/{fileName}", methods={"GET"}, name="financing-provisioning-download-signed-pdf")
     */
    public function download(
        ManagerRegistry $managerRegistry,
        $id,
        $fileName
    ): Response
    {
        $em = $managerRegistry->getManager();
        $entity = $em->getRepository(FinancingProvisioningCertification::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('ApplicationAttachment not found');
        }

        $fs = $this->filesystemMap->get('financing_provisioning');

        $response = new Response($fs->read($entity->getFileName()));
        $response->headers->set('Content-Type', 'application/octet-stream');
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $entity->getFileName());
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

}

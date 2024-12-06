<?php

namespace App\Controller;

use App\Entity\ApplicationMessageAttachment;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/application-message-attachments')]
class ApplicationMessageAttachmentController extends AbstractController
{
    /** @var FilesystemMap */
    private $filesystemMap;

    /**
     * ApplicationImportCrudController constructor.
     * @param FilesystemMap $filesystemMap
     */
    public function __construct(
        FilesystemMap $filesystemMap
    ) {
        $this->filesystemMap = $filesystemMap;
    }

    #[Route(path: '/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteAction(
        ManagerRegistry $managerRegistry,
        $id
    ): JsonResponse
    {
        $em = $managerRegistry->getManager();
        $messageAttachment = $em->getRepository(ApplicationMessageAttachment::class)->find($id);

        // TODO: implement voter for this action
        if (!$messageAttachment) {
            throw $this->createNotFoundException('ApplicationMessageAttachment not found');
        }

        $em->remove($messageAttachment);
        $em->flush();

        $response = new JsonResponse();
        $response->setStatusCode(Response::HTTP_NO_CONTENT);

        return $response;
    }

    #[Route(path: '/{id}/download')]
    public function download(
        ManagerRegistry $managerRegistry,
        $id
    ): Response
    {
        $em = $managerRegistry->getManager();
        $entity = $em->getRepository(ApplicationMessageAttachment::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('ApplicationMessageAttachment not found');
        }

        $fs = $this->filesystemMap->get('application_message_attachment');

        $response = new Response($fs->read($entity->getFileName()));
        $response->headers->set('Content-Type', 'application/octet-stream');
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $entity->getFileName());
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}

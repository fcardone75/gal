<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @return Response
     * @Route(path="/elbHealthCheck", name="elb_health_check")
     */
    #[Route(path: '/elbHealthCheck', name: 'elb_health_check')]
    public function elbHealthCheck(): Response
    {
        // perform actions that grant health status if needed
        return new Response();
    }
}

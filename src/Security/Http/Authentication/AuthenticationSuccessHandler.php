<?php

namespace App\Security\Http\Authentication;

use App\Service\Contracts\Security\SecurityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    private SecurityManagerInterface $securityManager;

    public function __construct(
        SecurityManagerInterface $securityManager,
        HttpUtils $httpUtils,
        array $options = [],
        LoggerInterface $logger = null
    ) {
        parent::__construct($httpUtils, $options, $logger);
        $this->securityManager = $securityManager;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        if (($user = $token->getUser()) && $user instanceof \App\Model\UserInterface) {
            $this->securityManager->setUserLastLogin($user);
            $this->logger->info("User login: Account {user} has logged in. Client IP: {client_ip}", [
                'user' => $user->getEmail(),
                'client_ip' => $request->getClientIp()
            ]);
        }

        return parent::onAuthenticationSuccess($request, $token);
    }
}

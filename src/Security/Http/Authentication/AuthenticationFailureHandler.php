<?php

namespace App\Security\Http\Authentication;

use App\Service\Contracts\Security\SecurityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    private SecurityManagerInterface $securityManager;

    public function __construct(
        SecurityManagerInterface $securityManager,
        HttpKernelInterface $httpKernel,
        HttpUtils $httpUtils,
        array $options = [],
        LoggerInterface $logger = null
    ) {
        parent::__construct($httpKernel, $httpUtils, $options, $logger);
        $this->securityManager = $securityManager;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $exception = $exception->getPrevious() ?? $exception;
        
        switch (true) {
            /*
            case $exception instanceof CredentialsExpiredException &&
                $this->canHandleCredentialsExpiredException():
                $this->options['failure_path'] = $this->options['credentials_expired_failure_path'];
                $user = $exception->getUser();
                if ($user instanceof \App\Model\UserInterface) {
                    $this->securityManager->prepareUserForCredentialsUpdate($user);
                    $request->attributes->set('token', $user->getUpdatePasswordToken());
                }
                break;
            */

            case $exception instanceof UserNotFoundException:
                $this->logger->info("User login: User {user} not found. Client IP: {client_ip}", [
                    'user' => $exception->getUserIdentifier(),
                    'client_ip' => $request->getClientIp()
                ]);
                break;

            case $exception instanceof CredentialsExpiredException:
                //TODO: parametro config e ripristinare controllo canHandleCredentialsExpiredException ?
                $this->options['failure_path'] = 'security_update_password';
                $user = $exception->getUser();
                if ($user instanceof \App\Model\UserInterface) {
                    $this->securityManager->prepareUserForCredentialsUpdate($user);
                    $request->attributes->set('token', $user->getUpdatePasswordToken());
                    $this->logger->info("User login: User {user} has expired credentials. Client IP: {client_ip}", [
                        'user' => $user->getEmail(),
                        'client_ip' => $request->getClientIp()
                    ]);
                }
                break;

            case $exception instanceof BadCredentialsException:
                $userIdentifier = $request->request->get('email');
                $user = $this->securityManager->loadUser($userIdentifier);
                if ($user) {
                    $this->securityManager->addLoginFailureForUser($user, $request);
                    $this->logger->info("User login: Wrong authentication for {user}. Client IP: {client_ip}", [
                        'user' => $user->getEmail(),
                        'client_ip' => $request->getClientIp()
                    ]);
                }
                break;

        }

        return parent::onAuthenticationFailure($request, $exception);
    }

    /**
     * @return bool
     */
    protected function canHandleCredentialsExpiredException(): bool
    {
        return isset($this->options['credentials_expired_failure_path']);
//        return isset($this->options['failure_path_parameter']);
    }
}

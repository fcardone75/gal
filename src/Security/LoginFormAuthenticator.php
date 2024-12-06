<?php

namespace App\Security;

use App\Service\Contracts\Security\SecurityManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @deprecated
 */
class LoginFormAuthenticator extends FormLoginAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;

    /**
     * @var SecurityManagerInterface
     */
    private $securityManager;

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        SecurityManagerInterface $securityManager,
        HttpUtils $httpUtils,
        array $options = []
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->securityManager = $securityManager;
        $this->httpUtils = $httpUtils;
        $this->options = $options;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): null|Response
    {
        if (($user = $token->getUser()) && $user instanceof \App\Model\UserInterface) {
            $this->securityManager->setUserLastLogin($user);
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('admin'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        switch (true) {
            case $exception instanceof CredentialsExpiredException &&
                $this->canHandleCredentialsExpiredException():
                $this->options['failure_path'] = $this->options['credentials_expired_failure_path'];
                $user = $exception->getUser();
                if ($user instanceof \App\Model\UserInterface) {
                    $this->securityManager->prepareUserForCredentialsUpdate($user);
                    $request->attributes->set('token', $user->getUpdatePasswordToken());
                }
                $response = $this->httpUtils->createRedirectResponse($request, $this->options['failure_path']);
                break;
            case $exception instanceof BadCredentialsException:
                $credentials = $exception->getToken()->getCredentials();
                $user = $this->securityManager->loadUser($credentials['email']);
                if ($user) {
                    $this->securityManager->addLoginFailureForUser($user, $request);
                }
                $response = parent::onAuthenticationFailure($request, $exception);
                break;
            default:
                $response = parent::onAuthenticationFailure($request, $exception);

        }

        return $response;
    }

    /**
     * @return bool
     */
    protected function canHandleCredentialsExpiredException(): bool
    {
        return isset($this->options['credentials_expired_failure_path']);
    }

}

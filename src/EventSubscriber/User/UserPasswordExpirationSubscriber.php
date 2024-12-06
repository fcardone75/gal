<?php

namespace App\EventSubscriber\User;

use App\Entity\UserPassword;
use App\Model\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class UserPasswordExpirationSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var AdminUrlGenerator
     */
    private $adminUrlGenerator;

    /**
     * @var array
     */
    private array $config;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param Security $security
     * @param AdminUrlGenerator $adminUrlGenerator
     * @param TranslatorInterface $translator
     * @param array $config
     */
    public function __construct(
        TokenStorageInterface   $tokenStorage,
        Security                $security,
        AdminUrlGenerator       $adminUrlGenerator,
        TranslatorInterface     $translator,
        array                   $config
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->security = $security;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->config = $config;
        $this->translator = $translator;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $currentRoute = $request->attributes->get('_route');
        $routeName = $request->query->get('routeName');
        if ($currentRoute === 'admin' && $routeName === 'security_change_password') {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token && $this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $token->getUser();
            if (isset($this->config['password_lifetime'])) {
                $this->setUserCredentialsExpiration($user);
            }

            if ($user && method_exists($user, 'isCredentialsNonExpired') && !$user->isCredentialsNonExpired()) {
                $session = $request->getSession();
                $session->getFlashBag()->add('danger', $this->translator->trans('Credentials have expired.', [], 'security'));
                $url_redirect = $this->adminUrlGenerator
                    ->setRoute('security_change_password')
                    ->generateUrl();

                $response = new RedirectResponse($url_redirect);
                $event->setResponse($response);
            }
        }
    }

    /**
     * @param UserInterface $user
     * @return void
     */
    protected function setUserCredentialsExpiration(UserInterface $user)
    {
        $credentialsExpiresAt = new \DateTime();
        $now = new \DateTime();
        $credentialsExpired = true;
        $forcePasswordChangeNewUsers = isset($this->config['force_password_change_new_users']) && $this->config['force_password_change_new_users'];
        $forcePasswordChangeNewUsersExcludeRoles = $this->config['force_password_change_new_users_exclude_roles'] ?? [];
        $userGroupShouldChangePasswordAtFirstLogin = !$forcePasswordChangeNewUsersExcludeRoles || !array_intersect($user->getRoles(), $forcePasswordChangeNewUsersExcludeRoles);

        $checkGeneratedPwd = $forcePasswordChangeNewUsers && $userGroupShouldChangePasswordAtFirstLogin && !$user->getLastLogin();

        $pwd = $user->getPassword();
        $currentPassword = $user->getUserPasswords()->filter(function(UserPassword $i) use ($pwd, $checkGeneratedPwd){
            $pwdMatch = $i->getPasswordHash() === $pwd;
            if ($checkGeneratedPwd) {
                $pwdMatch = $pwdMatch && !$i->getIsGenerated();
            }
            return $pwdMatch;
        })->first();

        if ($currentPassword && isset($this->config['password_lifetime'])) {
            $credentialsExpiresAtTs = (int) $currentPassword->getCreatedAt()->format('U') + $this->config['password_lifetime'];
            $credentialsExpiresAt->setTimestamp($credentialsExpiresAtTs);
            $credentialsExpired = $credentialsExpiresAt < $now;
        }


        $user->setCredentialsExpiresAt($credentialsExpiresAt);
        $user->setCredentialsExpired($credentialsExpired);
    }
}

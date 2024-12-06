<?php


namespace App\Service\Security;


use App\Entity\User;
use App\Model\UserInterface;
use App\Service\Contracts\Security\RoleProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoleProvider implements RoleProviderInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TranslatorInterface */
    private $translator;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var array */
    private $roleHierarchy;

    /** @var array */
    private $assignableRoles;

    /** @var array  */
    private $config = [];

    /**
     * RoleProvider constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface $translator
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param array $roleHierarchy
     * @param array $assignableRoles
     * @param array $config
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        array $roleHierarchy = [],
        array $assignableRoles = [],
        array $config = []
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->roleHierarchy = $roleHierarchy;
        $this->assignableRoles = $assignableRoles;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getRolesListAsOptionArray(): array
    {
        $configuredRoles = array_merge(
            [],
            array_keys($this->roleHierarchy)
        );
        $configuredRoles = array_filter($configuredRoles, function($configuredRole){
            return in_array($configuredRole, $this->getAssignableRoles());
        });
        $roles = [];
        $allowedRoles = $this->getRolesAllowedToAccess();
        if ($allowedRoles) {
            foreach ($configuredRoles as $configuredRole) {
                if (in_array($configuredRole, $allowedRoles)) {
                    $roles[$this->translator->trans($configuredRole, [], 'roles')] = $configuredRole;
                }
            }
        }

        return $roles;
    }

    /**
     * @return array
     */
    public function getRolesAllowedToAccess(): array
    {
        $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;
        $allowedRoles = [];

        if ($user !== 'anon.' && isset($this->config['role_acl_map'])) {
            foreach ($user->getRoles() as $userRole) {
                if (isset($this->config['role_acl_map'][$userRole]) && $configuredRoles = $this->config['role_acl_map'][$userRole]) {
                    if ($configuredRoles === '*') {
                        $allowedRoles = $this->getAssignableRoles();
                        break;
                    }
                    if (is_array($configuredRoles)) {
                        foreach ($configuredRoles as $configuredRole) {
                            if (!in_array($configuredRole, $allowedRoles)) {
                                $allowedRoles[] = $configuredRole;
                            }
                        }
                    }
                }
            }
        }

        return $allowedRoles;
    }


    /**
     * {@inheritDoc}
     */
    public function normalizeRoleLabel(string $roleLabel): string
    {
        return strtoupper('ROLE_' . implode('_', explode(' ', $roleLabel)));
    }

    public function userHasRoleArtigiancassa($user): bool
    {
        return in_array("ROLE_OPERATORE_ARTIGIANCASSA", $user->getRoles());
    }

    public function userHasRoleConfidi($user): bool
    {
        return in_array("ROLE_OPERATORE_CONFIDI", $user->getRoles());
    }

    public function getAssignableRoles(): array
    {
        if ($this->tokenStorage->getToken() &&
            $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN') &&
            !in_array('ROLE_SUPER_ADMIN', $this->assignableRoles)) {
            array_unshift($this->assignableRoles, 'ROLE_SUPER_ADMIN');
        }
        return $this->assignableRoles;
    }
}

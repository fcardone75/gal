<?php


namespace App\Service\Contracts\Security;


use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

interface RoleProviderInterface
{
    /**
     * @return array
     */
    public function getRolesListAsOptionArray(): array;

    /**
     * @return array
     */
    public function getRolesAllowedToAccess(): array;


    /**
     * @param string $roleLabel
     * @return string
     */
    public function normalizeRoleLabel(string $roleLabel): string;

    /**
     * @param User|UserInterface $user
     * @return bool
     */
    public function userHasRoleArtigiancassa($user): bool;

    /**
     * @param User|UserInterface $user
     * @return bool
     */
    public function userHasRoleConfidi($user): bool;

    /**
     * @return array
     */
    public function getAssignableRoles(): array;
}

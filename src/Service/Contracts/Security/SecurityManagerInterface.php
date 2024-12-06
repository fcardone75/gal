<?php


namespace App\Service\Contracts\Security;


use App\Model\UserInterface;
use Symfony\Component\HttpFoundation\Request;

interface SecurityManagerInterface
{
    /**
     * @param UserInterface $user
     * @param bool $andFlush
     * @return void
     */
    public function setUserLastLogin(UserInterface $user, bool $andFlush = true);

    /**
     * @param UserInterface $user
     * @param Request|null $request
     * @param bool $andFlush
     * @return void
     */
    public function addLoginFailureForUser(UserInterface $user, Request $request = null, bool $andFlush = true);

    /**
     * @param UserInterface $user
     * @param bool $andFlush
     * @return void
     */
    public function prepareUserForCredentialsUpdate(UserInterface $user, bool $andFlush = true);

    /**
     * @param UserInterface $user
     * @param bool $andFlush
     * @return void
     * @throws \LogicException
     */
    public function prepareUserForResetPassword(UserInterface $user, bool $andFlush = true);

    /**
     * @param UserInterface $user
     * @param bool $andFlush
     * @return void
     */
    public function resetUserPassword(UserInterface $user, $andFlush = true);

    /**
     * @param string $user
     * @return mixed
     */
    public function loadUser($user);
}

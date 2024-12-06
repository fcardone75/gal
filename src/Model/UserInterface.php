<?php

namespace App\Model;

use Doctrine\Common\Collections\Collection;
use App\Entity\LoginFailure;
use App\Entity\UserPassword;

interface UserInterface extends \Symfony\Component\Security\Core\User\UserInterface
{
    /**
     * @return string
     */
    public function getPlainPassword(): ?string;

    /**
     * @param string $plainPassword
     * @return $this
     */
    public function setPlainPassword(string $plainPassword): UserInterface;

    /**
     * @return Collection
     */
    public function getUserPasswords(): Collection;

    /**
     * @param UserPassword $userPassword
     * @return $this
     */
    public function addUserPassword(UserPassword $userPassword): UserInterface;

    /**
     * @param UserPassword $userPassword
     * @return $this
     */
    public function removeUserPassword(UserPassword $userPassword): UserInterface;

    /**
     * @return Collection
     */
    public function getLoginFailures(): Collection;

    /**
     * @param LoginFailure $loginFailure
     * @return $this
     */
    public function addLoginFailure(LoginFailure $loginFailure): UserInterface;

    /**
     * @param LoginFailure $loginFailure
     * @return $this
     */
    public function removeLoginFailure(LoginFailure $loginFailure): UserInterface;

    /**
     * @return \DateTime|null
     */
    public function getLastLogin(): ?\DateTime;

    /**
     * @param \DateTime|null $time
     * @return $this
     */
    public function setLastLogin(\DateTime $time = null): UserInterface;

    /**
     * @return bool
     */
    public function isCredentialsNonExpired(): bool;

    /**
     * @return bool
     */
    public function isCredentialsExpired(): bool;

    /**
     * @param bool $expired
     * @return $this
     */
    public function setCredentialsExpired(bool $expired): UserInterface;

    /**
     * @return \DateTime|null
     */
    public function getCredentialsExpiresAt(): ?\DateTime;

    /**
     * @param \DateTime|null $expiresAt
     * @return $this
     */
    public function setCredentialsExpiresAt(\DateTime $expiresAt = null): UserInterface;

    /**
     * @return bool
     */
    public function isAccountNonLocked(): bool;

    /**
     * @return bool
     */
    public function isLocked(): bool;

    /**
     * @param bool $locked
     * @return $this
     */
    public function setLocked(bool $locked);

    /**
     * @return \DateTimeInterface|null
     */
    public function getExpiresAt(): ?\DateTimeInterface;

    /**
     * @param \DateTimeInterface|null $expiresAt
     * @return UserInterface
     */
    public function setExpiresAt(?\DateTimeInterface $expiresAt): UserInterface;

    /**
     * @return string
     */
    public function getUpdatePasswordToken(): string;

    /**
     * @param string $token
     * @return $this
     */
    public function setUpdatePasswordToken(string $token);

    /**
     * @return string
     */
    public function getResetPasswordToken(): ?string;

    /**
     * @param null|string $token
     * @return $this
     */
    public function setResetPasswordToken(?string $token): UserInterface;

    /**
     * @return \DateTimeInterface|null
     */
    public function getResetPasswordRequestedAt(): ?\DateTimeInterface;

    /**
     * @param \DateTimeInterface|null $resetPasswordRequestedAt
     * @return UserInterface
     */
    public function setResetPasswordRequestedAt(?\DateTimeInterface $resetPasswordRequestedAt): UserInterface;

    /**
     * @param bool $generated
     * @return UserInterface
     */
    public function setPasswordIsGenerated(bool $generated): UserInterface;

    /**
     * @return bool
     */
    public function getPasswordIsGenerated(): bool;

    /**
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * @return bool
     */
    public function isAccountNonExpired(): bool;
}

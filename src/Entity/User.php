<?php

namespace App\Entity;

use App\Model\UserInterface;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\OneToMany(
     *     targetEntity=LoginFailure::class,
     *     mappedBy="user",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private $loginFailures;

    /**
     * @ORM\OneToMany(
     *     targetEntity=UserPassword::class,
     *     mappedBy="user",
     *     orphanRemoval=true,
     *     cascade={"persist"}
     * )
     */
    private $userPasswords;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $updatePasswordToken;

    /**
     * @var string
     */
    private $plainPassword;

    /**
     * @var bool
     */
    private $credentialsExpired = false;

    /**
     * @var \DateTime|null
     */
    private $credentialsExpiresAt = null;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var bool
     */
    private $passwordIsGenerated = false;

//    #[ORM\Column(type: "string", nullable: true)]
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $googleAuthenticatorSecret;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expiresAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $resetPasswordToken;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $resetPasswordRequestedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $mobilePhone;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $fiscalId;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birth;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $birthCountry;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $birthRegion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $birthProvince;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $birthCity;

    /**
     * @ORM\ManyToOne(targetEntity=Confidi::class, inversedBy="users")
     */
    private $confidi;

    public function __construct()
    {
        $this->loginFailures = new ArrayCollection();
        $this->userPasswords = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): UserInterface
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getUserPasswords(): Collection
    {
        return $this->userPasswords;
    }

    public function addUserPassword(UserPassword $userPassword): UserInterface
    {
        if (!$this->userPasswords->contains($userPassword)) {
            $userPassword->setUser($this);
            $this->userPasswords->add($userPassword);
        }
        return $this;
    }

    public function removeUserPassword(UserPassword $userPassword): UserInterface
    {
        if ($this->userPasswords->contains($userPassword)) {
            $this->userPasswords->removeElement($userPassword);
        }
        return $this;
    }

    public function getLoginFailures(): Collection
    {
        return $this->loginFailures;
    }

    public function addLoginFailure(LoginFailure $loginFailure): UserInterface
    {
        if (!$this->loginFailures->contains($loginFailure)) {
            $loginFailure->setUser($this);
            $this->loginFailures->add($loginFailure);
        }
        return $this;
    }

    public function removeLoginFailure(LoginFailure $loginFailure): UserInterface
    {
        if ($this->loginFailures->contains($loginFailure)) {
            $this->loginFailures->removeElement($loginFailure);
        }
        return $this;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(\DateTime $time = null): UserInterface
    {
        $this->lastLogin = $time;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): UserInterface
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getUpdatePasswordToken(): string
    {
        return $this->updatePasswordToken;
    }

    public function setUpdatePasswordToken(string $token = null)
    {
        $this->updatePasswordToken = $token;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?string $token): UserInterface
    {
        $this->resetPasswordToken = $token;

        return $this;
    }

    public function getResetPasswordRequestedAt(): ?\DateTimeInterface
    {
        return $this->resetPasswordRequestedAt;
    }

    public function setResetPasswordRequestedAt(?\DateTimeInterface $resetPasswordRequestedAt): UserInterface
    {
        $this->resetPasswordRequestedAt = $resetPasswordRequestedAt;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function setMobilePhone(?string $mobilePhone): self
    {
        $this->mobilePhone = $mobilePhone;

        return $this;
    }

    public function getFiscalId(): ?string
    {
        return $this->fiscalId;
    }

    public function setFiscalId(?string $fiscalId): self
    {
        $this->fiscalId = $fiscalId;

        return $this;
    }

    public function getBirth(): ?\DateTimeInterface
    {
        return $this->birth;
    }

    public function setBirth(?\DateTimeInterface $birth): self
    {
        $this->birth = $birth;

        return $this;
    }

    public function getBirthCountry(): ?string
    {
        return $this->birthCountry;
    }

    public function setBirthCountry(?string $birthCountry): self
    {
        $this->birthCountry = $birthCountry;

        return $this;
    }

    public function getBirthRegion(): ?string
    {
        return $this->birthRegion;
    }

    public function setBirthRegion(?string $birthRegion): self
    {
        $this->birthRegion = $birthRegion;

        return $this;
    }

    public function getBirthProvince(): ?string
    {
        return $this->birthProvince;
    }

    public function setBirthProvince(?string $birthProvince): self
    {
        $this->birthProvince = $birthProvince;

        return $this;
    }

    public function getBirthCity(): ?string
    {
        return $this->birthCity;
    }

    public function setBirthCity(?string $birthCity): self
    {
        $this->birthCity = $birthCity;

        return $this;
    }

    public function getConfidi(): ?Confidi
    {
        return $this->confidi;
    }

    public function setConfidi(?Confidi $confidi): self
    {
        $this->confidi = $confidi;

        return $this;
    }

    public function isCredentialsExpired(): bool
    {
        return $this->credentialsExpired;
    }

    public function setCredentialsExpired(bool $expired): UserInterface
    {
        $this->credentialsExpired = $expired;

        return $this;
    }

    public function getCredentialsExpiresAt(): ?\DateTime
    {
        return $this->credentialsExpiresAt;
    }

    public function setCredentialsExpiresAt(\DateTime $expiresAt = null): UserInterface
    {
        $this->credentialsExpiresAt = $expiresAt;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked)
    {
        $this->locked = $locked;

        return $this;
    }

    public function setPasswordIsGenerated(bool $generated): UserInterface
    {
        $this->passwordIsGenerated = $generated;

        return $this;
    }

    public function getPasswordIsGenerated(): bool
    {
        return $this->passwordIsGenerated;
    }

    public function isCredentialsNonExpired(): bool
    {
        return !$this->credentialsExpired;
    }

    public function isAccountNonLocked(): bool
    {
        return !$this->locked;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isAccountNonExpired(): bool
    {
        return null === $this->expiresAt || (($now = new \DateTime()) && $now < $this->expiresAt);
    }

    public function getFullName(): string
    {
        return implode(' ', [$this->getFirstName(), $this->getLastName()]);
    }

    /**
     * @ORM\PreFlush()
     */
    public function preFlush()
    {
        $this->setRoles($this->getRoles());
    }

    public function __toString()
    {
        if ($this->firstName || $this->lastName) {
            return implode (' ', [
                $this->firstName,
                $this->lastName
            ]);
        }
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }


    public function isGoogleAuthenticatorEnabled(): bool
    {
        return null !== $this->googleAuthenticatorSecret;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }
}

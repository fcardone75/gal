<?php

namespace App\Entity;

use App\Repository\ConfidiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConfidiRepository::class)
 */
class Confidi
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $businessName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $legalRepresentative;

    /**
     * @ORM\Column(type="string", length=27)
     */
    private $iban;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $fiscalCode;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $nsiaCode;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="confidi")
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity=ApplicationGroup::class, mappedBy="confidi", orphanRemoval=true)
     */
    private $applicationGroups;



    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaTotGarantito;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaTotRiservaAccantonata;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaTotInefficace;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaTotEscusso;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaTotEscutibile;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nsiaNumeroPratichePresentate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nsiaNumeroPraticheApprovate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nsiaNumeroPraticheInEssere;



    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->applicationGroups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(string $businessName): self
    {
        $this->businessName = $businessName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLegalRepresentative()
    {
        return $this->legalRepresentative;
    }

    /**
     * @param mixed $legalRepresentative
     */
    public function setLegalRepresentative($legalRepresentative): self
    {
        $this->legalRepresentative = $legalRepresentative;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getFiscalCode(): ?string
    {
        return $this->fiscalCode;
    }

    public function setFiscalCode(string $fiscalCode): self
    {
        $this->fiscalCode = $fiscalCode;

        return $this;
    }

    public function getNsiaCode(): ?string
    {
        return $this->nsiaCode;
    }

    public function setNsiaCode(?string $nsiaCode): self
    {
        $this->nsiaCode = $nsiaCode;

        return $this;
    }



    public function getNsiaTotGarantito(): ?float
    {
        return $this->nsiaTotGarantito;
    }

    public function setNsiaTotGarantito(?float $nsiaTotGarantito): self
    {
        $this->nsiaTotGarantito = $nsiaTotGarantito;

        return $this;
    }

    public function getNsiaTotRiservaAccantonata(): ?float
    {
        return $this->nsiaTotRiservaAccantonata;
    }

    public function setNsiaTotRiservaAccantonata(?float $nsiaTotRiservaAccantonata): self
    {
        $this->nsiaTotRiservaAccantonata = $nsiaTotRiservaAccantonata;

        return $this;
    }

    public function getNsiaTotInefficace(): ?float
    {
        return $this->nsiaTotInefficace;
    }

    public function setNsiaTotInefficace(?float $nsiaTotInefficace): self
    {
        $this->nsiaTotInefficace = $nsiaTotInefficace;

        return $this;
    }

    public function getNsiaTotEscusso(): ?float
    {
        return $this->nsiaTotEscusso;
    }

    public function setNsiaTotEscusso(?float $nsiaTotEscusso): self
    {
        $this->nsiaTotEscusso = $nsiaTotEscusso;

        return $this;
    }

    public function getNsiaTotEscutibile(): ?float
    {
        return $this->nsiaTotEscutibile;
    }

    public function setNsiaTotEscutibile(?float $nsiaTotEscutibile): self
    {
        $this->nsiaTotEscutibile = $nsiaTotEscutibile;

        return $this;
    }

    public function getNsiaNumeroPratichePresentate(): ?int
    {
        return $this->nsiaNumeroPratichePresentate;
    }

    public function setNsiaNumeroPratichePresentate(?int $nsiaNumeroPratichePresentate): self
    {
        $this->nsiaNumeroPratichePresentate = $nsiaNumeroPratichePresentate;

        return $this;
    }

    public function getNsiaNumeroPraticheApprovate(): ?int
    {
        return $this->nsiaNumeroPraticheApprovate;
    }

    public function setNsiaNumeroPraticheApprovate(?int $nsiaNumeroPraticheApprovate): self
    {
        $this->nsiaNumeroPraticheApprovate = $nsiaNumeroPraticheApprovate;

        return $this;
    }

    public function getNsiaNumeroPraticheInEssere(): ?int
    {
        return $this->nsiaNumeroPraticheInEssere;
    }

    public function setNsiaNumeroPraticheInEssere(?int $nsiaNumeroPraticheInEssere): self
    {
        $this->nsiaNumeroPraticheInEssere = $nsiaNumeroPraticheInEssere;

        return $this;
    }



    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setConfidi($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getConfidi() === $this) {
                $user->setConfidi(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->businessName;
    }

    /**
     * @return Collection|ApplicationGroup[]
     */
    public function getApplicationGroups(): Collection
    {
        return $this->applicationGroups;
    }

    public function addApplicationGroup(ApplicationGroup $applicationGroup): self
    {
        if (!$this->applicationGroups->contains($applicationGroup)) {
            $this->applicationGroups[] = $applicationGroup;
            $applicationGroup->setConfidi($this);
        }

        return $this;
    }

    public function removeApplicationGroup(ApplicationGroup $applicationGroup): self
    {
        if ($this->applicationGroups->removeElement($applicationGroup)) {
            // set the owning side to null (unless already changed)
            if ($applicationGroup->getConfidi() === $this) {
                $applicationGroup->setConfidi(null);
            }
        }

        return $this;
    }
}

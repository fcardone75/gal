<?php

namespace App\Entity;

use App\Repository\RegistryFileAuditRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=RegistryFileAuditRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true, hardDelete=false)
 */
class RegistryFileAudit
{
    const TYPE_LIGDO = 'LIGDO';
    const TYPE_LIGREND = 'LIGREND';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $fileName;

    /**
     * @ORM\Column(type="integer")
     */
    private $progressiveNumber;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity=Application::class, mappedBy="registryFileAudit")
     */
    private $applications;

    /**
     * @ORM\OneToMany(targetEntity=ApplicationGroup::class, mappedBy="registryFileAudit")
     */
    private $applicationGroups;

    /**
     * @ORM\OneToMany(targetEntity=FinancingProvisioningCertification::class, mappedBy="registryFileAudit")
     */
    private $financingProvisioningCertifications;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="created_by", onDelete="SET NULL")
     * @Gedmo\Blameable(on="create")
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="updated_by", onDelete="SET NULL")
     * @Gedmo\Blameable(on="update")
     */
    private $updatedBy;


    public function __construct()
    {
        $this->applications = new ArrayCollection();
        $this->applicationGroups = new ArrayCollection();
        $this->financingProvisioningCertifications = new ArrayCollection();
    }
    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getProgressiveNumber(): ?int
    {
        return $this->progressiveNumber;
    }

    public function setProgressiveNumber(int $progressiveNumber): self
    {
        $this->progressiveNumber = $progressiveNumber;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection|Application[]
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(Application $application): self
    {
        if (!$this->applications->contains($application)) {
            $this->applications[] = $application;
//TODO: verificare
//            $application->setApplicationImport($this);
            $application->setRegistryFileAudit($this);
        }

        return $this;
    }

    public function removeApplication(Application $application): self
    {
        if ($this->applications->removeElement($application)) {
            // set the owning side to null (unless already changed)
//TODO: verificare
//            if ($application->getApplicationImport() === $this) {
//                $application->setApplicationImport(null);
//            }
            if ($application->getRegistryFileAudit() === $this) {
                $application->setRegistryFileAudit(null);
            }
        }

        return $this;
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
            $applicationGroup->setRegistryFileAudit($this);
        }

        return $this;
    }

    public function removeApplicationGroup(ApplicationGroup $applicationGroup): self
    {
        if ($this->applicationGroups->removeElement($applicationGroup)) {
            // set the owning side to null (unless already changed)
            if ($applicationGroup->getRegistryFileAudit() === $this) {
                $applicationGroup->setRegistryFileAudit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|FinancingProvisioningCertification[]
     */
    public function getFinancingProvisioningCertifications(): Collection
    {
        return $this->financingProvisioningCertifications;
    }

    public function addFinancingProvisioningCertification(FinancingProvisioningCertification $financingProvisioningCertification): self
    {
        if (!$this->financingProvisioningCertifications->contains($financingProvisioningCertification)) {
            $this->financingProvisioningCertifications[] = $financingProvisioningCertification;
            $financingProvisioningCertification->setRegistryFileAudit($this);
        }

        return $this;
    }

    public function removeFinancingProvisioningCertification(FinancingProvisioningCertification $financingProvisioningCertification): self
    {
        if ($this->financingProvisioningCertifications->removeElement($financingProvisioningCertification)) {
            // set the owning side to null (unless already changed)
            if ($financingProvisioningCertification->getRegistryFileAudit() === $this) {
                $financingProvisioningCertification->setRegistryFileAudit(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}

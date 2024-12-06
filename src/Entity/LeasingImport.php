<?php

namespace App\Entity;

use App\Repository\LeasingImportRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=LeasingImportRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 */
class LeasingImport
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ApplicationImport", inversedBy="leasingImports")
     */
    private $applicationImport;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $practiceId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sfBankLeasing;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sfBusinessName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sfLeasingDestination;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $dclAmount;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $dclContractSignatureDate;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $dclResolutionDate;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $dclDuration;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $dclPeriodicity;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $dclFirstDeadline;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $dclFeeAmount;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    private $dclFeePercentage;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    private $dclRansomPercentage;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    private $dclRate;

    /**
     * @ORM\Column(type="integer", name="`row`", nullable=true)
     */
    private $row;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

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

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicationImport(): ?ApplicationImport
    {
        return $this->applicationImport;
    }

    public function setApplicationImport(ApplicationImport $applicationImport): self
    {
        $this->applicationImport = $applicationImport;

        return $this;
    }

    public function getPracticeId(): ?string
    {
        return $this->practiceId;
    }

    public function setPracticeId(string $practiceId): self
    {
        $this->practiceId = $practiceId;

        return $this;
    }

    public function getSfBankLeasing(): ?string
    {
        return $this->sfBankLeasing;
    }

    public function setSfBankLeasing(?string $sfBankLeasing): self
    {
        $this->sfBankLeasing = $sfBankLeasing;

        return $this;
    }

    public function getSfBusinessName(): ?string
    {
        return $this->sfBusinessName;
    }

    public function setSfBusinessName(?string $sfBusinessName): self
    {
        $this->sfBusinessName = $sfBusinessName;

        return $this;
    }

    public function getSfLeasingDestination(): ?string
    {
        return $this->sfLeasingDestination;
    }

    public function setSfLeasingDestination(?string $sfLeasingDestination): self
    {
        $this->sfLeasingDestination = $sfLeasingDestination;

        return $this;
    }

    public function getDclAmount(): ?string
    {
        return $this->dclAmount;
    }

    public function setDclAmount(?string $dclAmount): self
    {
        $this->dclAmount = $dclAmount;

        return $this;
    }

    public function getDclContractSignatureDate(): ?string
    {
        return $this->dclContractSignatureDate;
    }

    public function setDclContractSignatureDate(?string $dclContractSignatureDate): self
    {
        $this->dclContractSignatureDate = $dclContractSignatureDate;

        return $this;
    }

    public function getDclResolutionDate(): ?string
    {
        return $this->dclResolutionDate;
    }

    public function setDclResolutionDate(?string $dclResolutionDate): self
    {
        $this->dclResolutionDate = $dclResolutionDate;

        return $this;
    }

    public function getDclDuration(): ?string
    {
        return $this->dclDuration;
    }

    public function setDclDuration(?string $dclDuration): self
    {
        $this->dclDuration = $dclDuration;

        return $this;
    }

    public function getDclPeriodicity(): ?string
    {
        return $this->dclPeriodicity;
    }

    public function setDclPeriodicity(?string $dclPeriodicity): self
    {
        $this->dclPeriodicity = $dclPeriodicity;

        return $this;
    }

    public function getDclFirstDeadline(): ?string
    {
        return $this->dclFirstDeadline;
    }

    public function setDclFirstDeadline(?string $dclFirstDeadline): self
    {
        $this->dclFirstDeadline = $dclFirstDeadline;

        return $this;
    }

    public function getDclFeeAmount(): ?string
    {
        return $this->dclFeeAmount;
    }

    public function setDclFeeAmount(?string $dclFeeAmount): self
    {
        $this->dclFeeAmount = $dclFeeAmount;

        return $this;
    }

    public function getDclFeePercentage(): ?string
    {
        return $this->dclFeePercentage;
    }

    public function setDclFeePercentage(?string $dclFeePercentage): self
    {
        $this->dclFeePercentage = $dclFeePercentage;

        return $this;
    }

    public function getDclRansomPercentage(): ?string
    {
        return $this->dclRansomPercentage;
    }

    public function setDclRansomPercentage(?string $dclRansomPercentage): self
    {
        $this->dclRansomPercentage = $dclRansomPercentage;

        return $this;
    }

    public function getDclRate(): ?string
    {
        return $this->dclRate;
    }

    public function setDclRate(?string $dclRate): self
    {
        $this->dclRate = $dclRate;

        return $this;
    }

    public function getRow(): ?int
    {
        return $this->row;
    }

    public function setRow(?int $row): self
    {
        $this->row = $row;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }
}

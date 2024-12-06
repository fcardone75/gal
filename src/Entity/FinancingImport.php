<?php

namespace App\Entity;

use App\Repository\FinancingImportRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=FinancingImportRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 */
class FinancingImport
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ApplicationImport", inversedBy="financingImports")
     */
    private $applicationImport;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $practiceId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dbfBank;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dbfBusinessName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dbfABI;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fFinancialDestination;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $dfLoanProvidedAtImport;


    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $dfAmount;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $dfContractSignatureDate;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $dfResolutionDate;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $dfIssueDate;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $dfDuration;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $dfPeriodicity;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $dfFirstDepreciationDeadline;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $dfPreDepreciationExists;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $dfInstallmentAmount;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $tRateType;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    private $tRate;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    private $tTaeg;

    /**
     * @ORM\Column(type="integer", name="`row`", nullable=true)
     */
    private $row;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
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

    public function getDbfBank(): ?string
    {
        return $this->dbfBank;
    }

    public function setDbfBank(?string $dbfBank): self
    {
        $this->dbfBank = $dbfBank;

        return $this;
    }

    public function getDbfBusinessName(): ?string
    {
        return $this->dbfBusinessName;
    }

    public function setDbfBusinessName(?string $dbfBusinessName): self
    {
        $this->dbfBusinessName = $dbfBusinessName;

        return $this;
    }

    public function getDbfABI(): ?string
    {
        return $this->dbfABI;
    }

    public function setDbfABI(?string $dbfABI): self
    {
        $this->dbfABI = $dbfABI;

        return $this;
    }

    public function getFFinancialDestination(): ?string
    {
        return $this->fFinancialDestination;
    }

    public function setFFinancialDestination(?string $fFinancialDestination): self
    {
        $this->fFinancialDestination = $fFinancialDestination;

        return $this;
    }

    public function getDfLoanProvidedAtImport(): ?string
    {
        return $this->dfLoanProvidedAtImport;
    }

    public function setDfLoanProvidedAtImport(?string $dfLoanProvidedAtImport): self
    {
        $this->dfLoanProvidedAtImport = $dfLoanProvidedAtImport;

        return $this;
    }

    public function getDfAmount(): ?string
    {
        return $this->dfAmount;
    }

    public function setDfAmount(?string $dfAmount): self
    {
        $this->dfAmount = $dfAmount;

        return $this;
    }

    public function getDfContractSignatureDate(): ?string
    {
        return $this->dfContractSignatureDate;
    }

    public function setDfContractSignatureDate(?string $dfContractSignatureDate): self
    {
        $this->dfContractSignatureDate = $dfContractSignatureDate;

        return $this;
    }

    public function getDfResolutionDate(): ?string
    {
        return $this->dfResolutionDate;
    }

    public function setDfResolutionDate(?string $dfResolutionDate): self
    {
        $this->dfResolutionDate = $dfResolutionDate;

        return $this;
    }

    public function getDfIssueDate(): ?string
    {
        return $this->dfIssueDate;
    }

    public function setDfIssueDate(?string $dfIssueDate): self
    {
        $this->dfIssueDate = $dfIssueDate;

        return $this;
    }

    public function getDfDuration(): ?string
    {
        return $this->dfDuration;
    }

    public function setDfDuration(?string $dfDuration): self
    {
        $this->dfDuration = $dfDuration;

        return $this;
    }

    public function getDfPeriodicity(): ?string
    {
        return $this->dfPeriodicity;
    }

    public function setDfPeriodicity(?string $dfPeriodicity): self
    {
        $this->dfPeriodicity = $dfPeriodicity;

        return $this;
    }

    public function getDfFirstDepreciationDeadline(): ?string
    {
        return $this->dfFirstDepreciationDeadline;
    }

    public function setDfFirstDepreciationDeadline(?string $dfFirstDepreciationDeadline): self
    {
        $this->dfFirstDepreciationDeadline = $dfFirstDepreciationDeadline;

        return $this;
    }

    public function getDfPreDepreciationExists(): ?string
    {
        return $this->dfPreDepreciationExists;
    }

    public function setDfPreDepreciationExists(?string $dfPreDepreciationExists): self
    {
        $this->dfPreDepreciationExists = $dfPreDepreciationExists;

        return $this;
    }

    public function getDfInstallmentAmount(): ?string
    {
        return $this->dfInstallmentAmount;
    }

    public function setDfInstallmentAmount(?string $dfInstallmentAmount): self
    {
        $this->dfInstallmentAmount = $dfInstallmentAmount;

        return $this;
    }

    public function getTRateType(): ?string
    {
        return $this->tRateType;
    }

    public function setTRateType(string $tRateType): self
    {
        $this->tRateType = $tRateType;

        return $this;
    }

    public function getTRate(): ?string
    {
        return $this->tRate;
    }

    public function setTRate(?string $tRate): self
    {
        $this->tRate = $tRate;

        return $this;
    }

    public function getTTaeg(): ?string
    {
        return $this->tTaeg;
    }

    public function setTTaeg(?string $tTaeg): self
    {
        $this->tTaeg = $tTaeg;

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

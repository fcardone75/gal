<?php

namespace App\Entity;

use App\Repository\FinancingProvisioningCertificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=FinancingProvisioningCertificationRepository::class)
 * @Vich\Uploadable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true, hardDelete=false)
 */
class FinancingProvisioningCertification
{
    const STATUS_PENDING = 'pending';
    const STATUS_DOWNLOADED = 'downloaded';
    const STATUS_COMPLETED = 'completed';


    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * @ORM\Column(type="date")
     */
    private $contractSignatureDate;

    /**
     * @ORM\Column(type="date")
     */
    private $issueDate;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $periodicity;

    /**
     * @ORM\Column(type="date")
     */
    private $firstDepreciationDeadline;

    /**
     * @ORM\Column(type="date")
     */
    private $lastDepreciationDeadline;

    /**
     * @ORM\Column(type="integer")
     */
    private $preDepreciation;

    /**
     * @ORM\Column(type="float")
     */
    private $installmentAmount;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $rateType;

    /**
     * @ORM\Column(type="float")
     */
    private $rate;

    /**
     * @ORM\Column(type="float")
     */
    private $taeg;

    /**
     * @ORM\Column(type="float")
     */
    private $spread;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $status = self::STATUS_PENDING;

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

    /**
     * @ORM\OneToOne(targetEntity=Application::class, inversedBy="financingProvisioningCertification", cascade={"persist", "remove"})
     */
    private $application;

    /**
     * @ORM\ManyToOne(targetEntity=RegistryFileAudit::class, inversedBy="financingProvisioningCertifications")
     * @ORM\JoinColumn(nullable=true)
     */
    private $registryFileAudit;




    /**
     * @ORM\Column(type="integer")
     */
    private $financingDuration;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $filename;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $originalFileName;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $fileSize;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fileUploadedAt;

    /**
     * @var File|null
     * @Vich\UploadableField(mapping="financing_provisioning", fileNameProperty="filename")
     */
    private $filenameFile;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $assuranceAmount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $assurancePercentage;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        $this->setAssurancePercentage();
        $this->setAssuranceAmount();
        return $this;
    }

    public function getContractSignatureDate(): ?\DateTimeInterface
    {
        return $this->contractSignatureDate;
    }

    public function setContractSignatureDate(?\DateTimeInterface $contractSignatureDate): self
    {
        $this->contractSignatureDate = $contractSignatureDate;

        return $this;
    }

    public function getIssueDate(): ?\DateTimeInterface
    {
        return $this->issueDate;
    }

    public function setIssueDate(?\DateTimeInterface $issueDate): self
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getPeriodicity(): ?string
    {
        return $this->periodicity;
    }

    public function setPeriodicity(string $periodicity): self
    {
        $this->periodicity = $periodicity;

        return $this;
    }

    public function getFirstDepreciationDeadline(): ?\DateTimeInterface
    {
        return $this->firstDepreciationDeadline;
    }

    public function setFirstDepreciationDeadline(?\DateTimeInterface $firstDepreciationDeadline): self
    {
        $this->firstDepreciationDeadline = $firstDepreciationDeadline;

        return $this;
    }

    public function getPreDepreciation(): ?string
    {
        return $this->preDepreciation;
    }

    public function setPreDepreciation(string $preDepreciation): self
    {
        $this->preDepreciation = $preDepreciation;

        return $this;
    }

    public function getInstallmentAmount(): ?float
    {
        return $this->installmentAmount;
    }

    public function setInstallmentAmount(float $installmentAmount): self
    {
        $this->installmentAmount = $installmentAmount;

        return $this;
    }

    public function getRateType(): ?string
    {
        return $this->rateType;
    }

    public function setRateType(string $rateType): self
    {
        $this->rateType = $rateType;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getTaeg(): ?float
    {
        return $this->taeg;
    }

    public function setTaeg(float $taeg): self
    {
        $this->taeg = $taeg;

        return $this;
    }

    public function getSpread(): ?float
    {
        return $this->spread;
    }

    public function setSpread(float $spread): self
    {
        $this->spread = $spread;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
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

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): self
    {
        $this->application = $application;

        return $this;
    }

    public function getRegistryFileAudit(): ?RegistryFileAudit
    {
        return $this->registryFileAudit;
    }

    public function setRegistryFileAudit(?RegistryFileAudit $registryFileAudit): self
    {
        $this->registryFileAudit = $registryFileAudit;

        return $this;
    }

    public function getFinancingDuration(): ?int
    {
        return $this->financingDuration;
    }

    public function setFinancingDuration(int $financingDuration): self
    {
        $this->financingDuration = $financingDuration;

        return $this;
    }


    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename($filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getOriginalFileName(): ?string
    {
        return $this->originalFileName;
    }

    public function setOriginalFileName($originalFileName): self
    {
        $this->originalFileName = $originalFileName;

        return $this;
    }

    public function getFileSize()
    {
        return $this->fileSize;
    }

    public function setFileSize($fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getFileUploadedAt(): ?\DateTimeInterface
    {
        return $this->fileUploadedAt;
    }

    public function setFileUploadedAt(?\DateTimeInterface $fileUploadedAt): self
    {
        $this->fileUploadedAt = $fileUploadedAt;

        return $this;
    }

    public function getFilenameFile(): ?File
    {
        return $this->filenameFile;
    }

    public function setFilenameFile(?File $filenameFile): self
    {
        $this->filenameFile = $filenameFile;

        if ($filenameFile instanceof UploadedFile) {
            $this->updatedAt = new \DateTime();
            $this->fileUploadedAt = new \DateTime();
            $this->setFileSize($filenameFile->getSize());
            $this->setOriginalFileName($filenameFile->getClientOriginalName());
        }

        return $this;
    }



    public function getLastDepreciationDeadline(): ?\DateTimeInterface
    {
        return $this->lastDepreciationDeadline;
    }

    public function setLastDepreciationDeadline(?\DateTimeInterface $lastDepreciationDeadline): self
    {
        $this->lastDepreciationDeadline = $lastDepreciationDeadline;

        return $this;
    }

    public function getAssuranceAmount(): ?float
    {
        return $this->assuranceAmount;
    }

    public function setAssuranceAmount(): self
    {
        if($this->getAmount() && $this->getAssurancePercentage()){
            $this->assuranceAmount = $this->getAmount() * $this->getAssurancePercentage()/100;
        } else {
            $this->assuranceAmount = null;
        }
        return $this;
    }

    public function getAssurancePercentage(): ?float
    {
        return $this->assurancePercentage;
    }

    public function setAssurancePercentage(): self
    {
        if($this->application && $this->application->getAeGAssuranceAmount() && $this->application->getFDfAmount()){
            $this->assurancePercentage = $this->application->getAeGAssuranceAmount()/$this->application->getFDfAmount()*100;
        } else {
            $this->assurancePercentage = null;
        }
        return $this;
    }

}

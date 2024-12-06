<?php

namespace App\Entity;

use App\Repository\ApplicationImportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;


/**
 * @ORM\Entity(repositoryClass=ApplicationImportRepository::class)
 * @Vich\Uploadable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true, hardDelete=false)
 */
class ApplicationImport
{
    const STATUS_NEW = 'new';
    const STATUS_FAILED = 'failed';
    const STATUS_ACQUIRED = 'acquired';
    const STATUS_VALIDATION_FAILED = 'validation_failed';
    const STATUS_VALIDATION_SUCCEEDED = 'validation_succeeded';
    const STATUS_IMPORTED = 'imported';

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
    private $filename;

    /**
     * @var File|null
     * @Assert\File(
     *       mimeTypes = {"application/xlsx", "application/xlsx", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"},
     *       mimeTypesMessage = "Formati accettati: xlsx"
     * )
     * @Vich\UploadableField(mapping="application_import", fileNameProperty="filename")
     */
    private $filenameFile;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $status = self::STATUS_NEW;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
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
     */
    private $updatedBy;

    /**
     * @ORM\ManyToOne(targetEntity=ApplicationImportTemplate::class)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $template;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\OneToMany(targetEntity=Application::class, mappedBy="applicationImport")
     */
    private $applications;

    /**
     * @ORM\OneToMany(targetEntity=AssuranceEnterpriseImport::class, mappedBy="applicationImport", cascade={"remove"})
     * @var Collection
     */
    private $assuranceEnterpriseImports;

    /**
     * @ORM\OneToMany(targetEntity=FinancingImport::class, mappedBy="applicationImport", cascade={"remove"})
     * @var Collection
     */
    private $financingImports;

    /**
     * @ORM\OneToMany(targetEntity=LeasingImport::class, mappedBy="applicationImport", cascade={"remove"})
     * @var Collection
     */
    private $leasingImports;

    /** @var Spreadsheet */
    private $spreadsheet;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $errors = [];

    /**
     * @ORM\ManyToOne(targetEntity=Confidi::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $confidi;




    public function __construct()
    {
        $this->applications = new ArrayCollection();
        $this->assuranceEnterpriseImports = new ArrayCollection();
        $this->financingImports = new ArrayCollection();
        $this->leasingImports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getFilenameFile(): ?File
    {
        return $this->filenameFile;
    }

    /**
     * @param File|null $filenameFile
     */
    public function setFilenameFile(?File $filenameFile): self
    {
        $this->filenameFile = $filenameFile;

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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getTemplate(): ?ApplicationImportTemplate
    {
        return $this->template;
    }

    public function setTemplate(?ApplicationImportTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public static function getStatusChoices()
    {
        return [
            'crud.application_import.statuses.new' => self::STATUS_NEW,
            'crud.application_import.statuses.failed' => self::STATUS_FAILED,
            'crud.application_import.statuses.acquired' => self::STATUS_ACQUIRED,
            'crud.application_import.statuses.validation_failed' => self::STATUS_VALIDATION_FAILED,
            'crud.application_import.statuses.validation_succeeded' => self::STATUS_VALIDATION_SUCCEEDED,
            'crud.application_import.statuses.imported' => self::STATUS_IMPORTED,
        ];
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
            $application->setApplicationImport($this);
        }

        return $this;
    }

    public function removeApplication(Application $application): self
    {
        if ($this->applications->removeElement($application)) {
            // set the owning side to null (unless already changed)
            if ($application->getApplicationImport() === $this) {
                $application->setApplicationImport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Application[]
     */
    public function getAssuranceEnterpriseImports(): Collection
    {
        return $this->assuranceEnterpriseImports;
    }

    public function addAssuranceEnterpriseImport(AssuranceEnterpriseImport $assuranceEnterpriseImport): self
    {
        if (!$this->assuranceEnterpriseImports->contains($assuranceEnterpriseImport)) {
            $this->assuranceEnterpriseImports[] = $assuranceEnterpriseImport;
            $assuranceEnterpriseImport->setApplicationImport($this);
        }

        return $this;
    }

    public function removeAssuranceEnterpriseImport(AssuranceEnterpriseImport $assuranceEnterpriseImport): self
    {
        if ($this->assuranceEnterpriseImports->removeElement($assuranceEnterpriseImport)) {
            // set the owning side to null (unless already changed)
            if ($assuranceEnterpriseImport->getApplicationImport() === $this) {
                $assuranceEnterpriseImport->setApplicationImport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Application[]
     */
    public function getFinancingImports(): Collection
    {
        return $this->financingImports;
    }

    public function addFinancingImport(FinancingImport $financingImport): self
    {
        if (!$this->financingImports->contains($financingImport)) {
            $this->financingImports[] = $financingImport;
            $financingImport->setApplicationImport($this);
        }

        return $this;
    }

    public function removeFinancingImport(FinancingImport $financingImport): self
    {
        if ($this->financingImports->removeElement($financingImport)) {
            // set the owning side to null (unless already changed)
            if ($financingImport->getApplicationImport() === $this) {
                $financingImport->setApplicationImport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Application[]
     */
    public function getLeasingImports(): Collection
    {
        return $this->leasingImports;
    }

    public function addLeasingImport(LeasingImport $leasingImport): self
    {
        if (!$this->leasingImports->contains($leasingImport)) {
            $this->leasingImports[] = $leasingImport;
            $leasingImport->setApplicationImport($this);
        }

        return $this;
    }

    public function removeLeasingImport(LeasingImport $leasingImport): self
    {
        if ($this->leasingImports->removeElement($leasingImport)) {
            // set the owning side to null (unless already changed)
            if ($leasingImport->getApplicationImport() === $this) {
                $leasingImport->setApplicationImport(null);
            }
        }

        return $this;
    }

    /**
     * @return Spreadsheet
     */
    public function getSpreadsheet(): ?Spreadsheet
    {
        return $this->spreadsheet;
    }

    /**
     * @param Spreadsheet|null $spreadsheet
     */
    public function setSpreadsheet(?Spreadsheet $spreadsheet): self
    {
        $this->spreadsheet = $spreadsheet;

        return $this;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function setErrors(?array $errors): self
    {
        $this->errors = $errors;

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

    public function getConfidi(): ?Confidi
    {
        return $this->confidi;
    }

    public function setConfidi(?Confidi $confidi): self
    {
        $this->confidi = $confidi;

        return $this;
    }
}

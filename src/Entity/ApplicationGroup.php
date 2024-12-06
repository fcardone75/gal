<?php

namespace App\Entity;

use App\Repository\ApplicationGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ApplicationGroupRepository::class)
 * @Vich\Uploadable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true, hardDelete=false)
 */
class ApplicationGroup
{
    const STATUS_NEW = 'new';
    const STATUS_REGISTERED = 'registered';
    const STATUS_SENT_TO_NSIA = 'sent_to_nsia';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $protocolNumber;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $protocolDate;

    /**
     * @ORM\Column(name="`status`", type="string", length=64)
     */
    private $status = self::STATUS_NEW;

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
     * @Vich\UploadableField(mapping="application_group", fileNameProperty="filename")
     */
    private $filenameFile;

    /**
     * @ORM\ManyToOne(targetEntity=RegistryFileAudit::class, inversedBy="applicationGroups")
     * @ORM\JoinColumn(nullable=true)
     */
    private $registryFileAudit;

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
     * @ORM\JoinColumn(name="created_by")
     * @Gedmo\Blameable(on="create")
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="updated_by")
     * @Gedmo\Blameable(on="update")
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\OneToMany(targetEntity=Application::class, mappedBy="applicationGroup")
     */
    private $applications;

    /**
     * @ORM\ManyToOne(targetEntity=Confidi::class, inversedBy="applicationGroups")
     * @ORM\JoinColumn(nullable=false)
     */
    private $confidi;

    public function __construct()
    {
        $this->applications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProtocolNumber(): ?string
    {
        return $this->protocolNumber;
    }

    public function setProtocolNumber(?string $protocolNumber): self
    {
        $this->protocolNumber = $protocolNumber;

        return $this;
    }

    public function getProtocolDate(): ?\DateTimeInterface
    {
        return $this->protocolDate;
    }

    public function setProtocolDate(?\DateTimeInterface $protocolDate): self
    {
        $this->protocolDate = $protocolDate;

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

    public function getRegistryFileAudit(): ?RegistryFileAudit
    {
        return $this->registryFileAudit;
    }

    public function setRegistryFileAudit(?RegistryFileAudit $registryFileAudit): self
    {
        $this->registryFileAudit = $registryFileAudit;

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
            $application->setApplicationGroup($this);
        }

        return $this;
    }

    public function removeApplication(Application $application): self
    {
        if ($this->applications->removeElement($application)) {
            // set the owning side to null (unless already changed)
            if ($application->getApplicationGroup() === $this) {
                $application->setApplicationGroup(null);
            }
        }

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

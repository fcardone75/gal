<?php

namespace App\Entity;

use App\Repository\ApplicationMessageAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ApplicationMessageAttachmentRepository::class)
 * @Vich\Uploadable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true, hardDelete=false)
 */
class ApplicationMessageAttachment
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
    private $fileName;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $fileSize;

    /**
     * @Vich\UploadableField(mapping="application_message_attachment", fileNameProperty="fileName")
     * @var File
     */
    private $uploadFile;

    /**
     * @var string
     */
    private $fileWebPath;

    /**
     * @var string
     */
    private $apiDelete;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $originalFileName;

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
     * @ORM\ManyToOne(targetEntity=ApplicationMessage::class, inversedBy="attachments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $applicationMessage;

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

    /**
     * @return mixed
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param mixed $fileSize
     */
    public function setFileSize($fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @param File $uploadFile
     */
    public function setUploadFile(File $uploadFile = null)
    {
        $this->uploadFile = $uploadFile;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($uploadFile) {
            // if 'updatedAt' is not defined in your entity, use another property
            $this->updatedAt = new \DateTime('now');
            if ($uploadFile instanceof UploadedFile) {
                $this->setOriginalFilename($uploadFile->getClientOriginalName());
                $this->setFileSize($uploadFile->getSize());
            }
        }
    }

    /**
     * @return File
     */
    public function getUploadFile()
    {
        return $this->uploadFile;
    }

    /**
     * @param string $apiDelete
     * @return ApplicationMessageAttachment
     */
    public function setApiDelete(string $apiDelete): ApplicationMessageAttachment
    {
        $this->apiDelete = $apiDelete;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiDelete()
    {
        return $this->apiDelete;
    }

    /**
     * @param string $fileWebPath
     */
    public function setFileWebPath(string $fileWebPath): ApplicationMessageAttachment
    {
        $this->fileWebPath = $fileWebPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileWebPath()
    {
        return $this->fileWebPath;
    }

    public function getOriginalFileName(): ?string
    {
        return $this->originalFileName;
    }

    public function setOriginalFileName(string $originalFileName): self
    {
        $this->originalFileName = $originalFileName;

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

    public function getApplicationMessage(): ?ApplicationMessage
    {
        return $this->applicationMessage;
    }

    public function setApplicationMessage(?ApplicationMessage $applicationMessage): self
    {
        $this->applicationMessage = $applicationMessage;

        return $this;
    }
}

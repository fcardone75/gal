<?php

namespace App\Entity;

use App\Repository\ApplicationImportTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ApplicationImportTemplateRepository::class)
 * @Vich\Uploadable
 */
class ApplicationImportTemplate
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
    private $filename;

    /**
     * @var File|null
     * @Assert\File(
     *       mimeTypes = {"application/xlsx", "application/xlsx", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"},
     *       mimeTypesMessage = "Formati accettati: xlsx"
     *   )
     * @Vich\UploadableField(mapping="application_import_template", fileNameProperty="filename")
     */
    private $filenameFile;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active = true;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="created_by", onDelete="SET NULL")
     * @Gedmo\Blameable(on="create")
     */
    private $createdBy;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $revision;

    /** @var Spreadsheet|null */
    private $spreadsheet;

    /** @var string|null */
    private $referencesSheetName;

    /** @var string|null */
    private $versionCell;

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
     * @param File $filenameFile
     */
    public function setFilenameFile(File $filenameFile): self
    {
        $this->filenameFile = $filenameFile;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

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

    public function getRevision(): ?string
    {
        return $this->revision;
    }

    public function setRevision(string $revision): self
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * @return Spreadsheet|null
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

    /**
     * @return string|null
     */
    public function getReferencesSheetName(): ?string
    {
        return $this->referencesSheetName;
    }

    /**
     * @param string|null $referencesSheetName
     */
    public function setReferencesSheetName(?string $referencesSheetName): self
    {
        $this->referencesSheetName = $referencesSheetName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVersionCell(): ?string
    {
        return $this->versionCell;
    }

    /**
     * @param string|null $versionCell
     */
    public function setVersionCell(?string $versionCell): self
    {
        $this->versionCell = $versionCell;

        return $this;
    }

    /**
     * @return ApplicationImportTemplate
     * @throws Exception
     * @throws \LogicException
     * @throws \Exception
     */
    public function updateRevisionFromReferenceSheet(): self
    {
        if (!$spreadsheet = $this->getSpreadsheet()) {
            throw new \LogicException('Unable to update revision number without a spreadsheet');
        }
        if (!$this->getReferencesSheetName()) {
            throw new \LogicException('Unable to update revision number without the references sheet name');
        }
        if (!$this->getVersionCell()) {
            throw new \LogicException('Unable to update revision number without version cell coordinates');
        }
        $referencesSheet = $spreadsheet->getSheetByName($this->getReferencesSheetName());
        if (!$referencesSheet) {
            throw new \Exception(sprintf('The given file does not contain a "%s" worksheet', $this->getReferencesSheetName()));
        }
        $versionCell = $referencesSheet->getCell($this->getVersionCell());

        if (!$versionCell || !preg_match('/^(\d+\.)?(\d+\.)?(\d+)$/', $versionCell->getValue())) {
            throw new \Exception(sprintf('The given file has an invalid version specified at cell "%2"', $versionCell));
        }

        $this->setRevision($versionCell->getValue());

        return $this;
    }

    public function __toString()
    {
        return implode(' ', ['REV', $this->revision]);
    }
}

<?php

namespace App\Entity;

use App\Repository\LegalFormRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LegalFormRepository::class)
 */
class LegalForm
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
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $referenceId;

    /**
     * @ORM\ManyToOne(targetEntity=ApplicationImportTemplate::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $template;

    /**
     * @ORM\Column(type="boolean")
     */
    private $cooperative;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getReferenceId(): ?int
    {
        return $this->referenceId;
    }

    public function setReferenceId(int $referenceId): self
    {
        $this->referenceId = $referenceId;

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

    public function getCooperative(): ?bool
    {
        return $this->cooperative;
    }

    public function setCooperative(bool $cooperative): self
    {
        $this->cooperative = $cooperative;

        return $this;
    }
}

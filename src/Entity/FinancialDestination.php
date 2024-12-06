<?php

namespace App\Entity;

use App\Repository\FinancialDestinationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FinancialDestinationRepository::class)
 */
class FinancialDestination
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
    private $destination;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $code;

    /**
     * @ORM\ManyToOne(targetEntity=ApplicationImportTemplate::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $template;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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
}

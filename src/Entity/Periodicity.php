<?php

namespace App\Entity;

use App\Repository\PeriodicityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PeriodicityRepository::class)
 */
class Periodicity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private $months;

    /**
     * @ORM\ManyToOne(targetEntity=ApplicationImportTemplate::class)
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $template;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMonths(): ?int
    {
        return $this->months;
    }

    public function setMonths(int $months): self
    {
        $this->months = $months;

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

<?php

namespace App\Entity;

use App\Repository\PurposeDirRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PurposeDirRepository::class)
 */
class PurposeDir
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $section;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subsection;

    /**
     * @ORM\Column(type="text")
     */
    private $name;

    /**
     * @ORM\Column(type="float")
     */
    private $kfValue;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(string $section): self
    {
        $this->section = $section;

        return $this;
    }

    public function getSubsection(): ?string
    {
        return $this->subsection;
    }

    public function setSubsection(string $subsection): self
    {
        $this->subsection = $subsection;

        return $this;
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

    public function getKfValue(): ?float
    {
        return $this->kfValue;
    }

    public function setKfValue(float $kfValue): self
    {
        $this->kfValue = $kfValue;

        return $this;
    }
}

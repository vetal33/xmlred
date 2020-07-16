<?php

namespace App\Entity;

use App\Repository\IndexingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=IndexingRepository::class)
 */
class Indexing
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $year;

    /**
     * @ORM\Column(type="float")
     */
    private $valueNonAgro;

    /**
     * @ORM\Column(type="float")
     */
    private $valueAgro;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getValueNonAgro(): ?float
    {
        return $this->valueNonAgro;
    }

    public function setValueNonAgro(float $valueNonAgro): self
    {
        $this->valueNonAgro = $valueNonAgro;

        return $this;
    }

    public function getValueAgro(): ?float
    {
        return $this->valueAgro;
    }

    public function setValueAgro(float $valueAgro): self
    {
        $this->valueAgro = $valueAgro;

        return $this;
    }
}

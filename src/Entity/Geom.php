<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GeomRepository")
 */
class Geom
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="geometry", nullable=true)
     */
    private $geom;

    /**
     * @ORM\Column(type="geometry", nullable=true)
     */
    private $originalGeom;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGeom(): ?string
    {
        return $this->geom;
    }

    public function setGeom(string $geom): self
    {
        $this->geom = $geom;

        return $this;
    }


    /**
     * @return string|null
     */
    public function getOriginalGeom(): ?string
    {
        return $this->originalGeom;
    }


    /**
     * @param $originalGeom
     * @return Geom
     */
    public function setOriginalGeom($originalGeom): self
    {
        $this->originalGeom = $originalGeom;

        return $this;
    }
}

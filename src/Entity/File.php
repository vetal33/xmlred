<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FileRepository")
 */
class File
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cadastr;

    /**
     * @ORM\Column(type="datetime")
     */
    private $addDate;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $xmlFileName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $comment;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCadastr(): ?string
    {
        return $this->cadastr;
    }

    public function setCadastr(?string $cadastr): self
    {
        $this->cadastr = $cadastr;

        return $this;
    }

    public function getAddDate(): ?\DateTimeInterface
    {
        return $this->addDate;
    }

    public function setAddDate(\DateTimeInterface $addDate): self
    {
        $this->addDate = $addDate;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getXmlFileName(): ?string
    {
        return $this->xmlFileName;
    }


    /**
     *
     * @param string|null $xmlFileName
     * @return File
     */
    public function setXmlFileName(?string $xmlFileName): self
    {
        $this->xmlFileName = $xmlFileName;
        return $this;
    }




}

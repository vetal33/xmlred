<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ParcelRepository")
 */
class Parcel
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=22)
     */
    private $cadNum;

    /**
     * @ORM\Column(name="area", type="float", nullable=true)
     *
     */
    private $area;

    /**
     * @ORM\Column(name="use", type="string", nullable=true, options={"comment":"Фактичне використання"})
     *
     */
    private $use;

    /**
     * @ORM\OneToOne(targetEntity="Geom", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    private $geom;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $userId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCadNum(): ?string
    {
        return $this->cadNum;
    }

    public function setCadNum(string $cadNum): self
    {
        $this->cadNum = $cadNum;

        return $this;
    }

    /**
     * @return Geom|null
     */
    public function getGeom(): ?Geom
    {
        return $this->geom;
    }


    /**
     * @param Geom $geom
     * @return Parcel
     */
    public function setGeom(Geom $geom): self
    {
        $this->geom = $geom;

        return $this;
    }

    /**
     * @return User
     */
    public function getUserId(): User
    {
        return $this->userId;
    }


    /**
     * @param User $userId
     * @return Parcel
     */
    public function setUserId(User $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getArea(): ?float
    {
        return $this->area;
    }

    /**
     * @param float|null $area
     * @return Parcel
     */
    public function setArea(?float $area): Parcel
    {
        $this->area = $area;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUse(): ?string
    {
        return $this->use;
    }


    /**
     * @param $use
     * @return Parcel
     */
    public function setUse(string $use): self
    {
        $this->use = $use;
        return $this;
    }
}

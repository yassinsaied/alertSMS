<?php

namespace App\Entity;

class Destinataire
{
    private ?int $id = null;
    private ?string $insee = null;
    private ?string $telephone = null;
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct(?int $id = null, ?string $insee = null, ?string $telephone = null, ?\DateTimeImmutable $createdAt = null)
    {
        $this->id = $id;
        $this->insee = $insee;
        $this->telephone = $telephone;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getInsee(): ?string
    {
        return $this->insee;
    }

    public function setInsee(?string $insee): self
    {
        $this->insee = $insee;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}

<?php

namespace App\Entity;

class SmsLog
{
    private ?int $id = null;
    private ?string $telephone = null;
    private ?string $message = null;
    private ?string $status = null;
    private ?\DateTimeImmutable $sentAt = null;
    private ?string $metadata = null;

    public function __construct(
        ?int $id = null,
        ?string $telephone = null,
        ?string $message = null,
        ?string $status = 'sent',
        ?\DateTimeImmutable $sentAt = null,
        ?string $metadata = null
    ) {
        $this->id = $id;
        $this->telephone = $telephone;
        $this->message = $message;
        $this->status = $status;
        $this->sentAt = $sentAt ?? new \DateTimeImmutable();
        $this->metadata = $metadata;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function setMetadata(?string $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
}
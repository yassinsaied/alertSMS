<?php

namespace App\Message;

class AlertMessage
{
    public function __construct(
        private string $telephone,
        private string $message,
        private string $insee,
        private array $metadata = []
    ) {
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getInsee(): string
    {
        return $this->insee;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
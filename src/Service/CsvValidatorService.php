<?php

namespace App\Service;

class CsvValidatorService
{
    public function validateInsee(string $insee): bool
    {
        // Code INSEE : 5 chiffres
        return preg_match('/^\d{5}$/', trim($insee)) === 1;
    }

    public function validateTelephone(string $telephone): bool
    {
        // Numéro tel : commence par 0, 10 chiffres
        $cleanPhone = preg_replace('/[\s\-\.]/', '', trim($telephone));
        return preg_match('/^0[1-9]\d{8}$/', $cleanPhone) === 1;
    }

    public function nettoyerTelephone(string $telephone): string
    {
        return preg_replace('/[\s\-\.]/', '', trim($telephone));
    }

    public function nettoyerInsee(string $insee): string
    {
        return trim($insee);
    }
}
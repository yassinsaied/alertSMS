<?php

namespace App\Repository;

use App\Entity\Destinataire;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class DestinataireRepository
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger
    ) {
    }

    public function save(Destinataire $destinataire): bool
    {
        try {
            $this->connection->insert('destinataires', [
                'insee' => $destinataire->getInsee(),
                'telephone' => $destinataire->getTelephone(),
                'created_at' => $destinataire->getCreatedAt()->format('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur sauvegarde destinataire', [
                'error' => $e->getMessage(),
                'insee' => $destinataire->getInsee(),
                'telephone' => $destinataire->getTelephone()
            ]);
            return false;
        }
    }

    public function findByInsee(string $insee): array
    {
        try {
            $result = $this->connection->fetchAllAssociative(
                'SELECT * FROM destinataires WHERE insee = ?',
                [$insee]
            );
            
            $destinataires = [];
            foreach ($result as $row) {
                $destinataire = new Destinataire();
                $destinataire->setId($row['id'])
                    ->setInsee($row['insee'])
                    ->setTelephone($row['telephone'])
                    ->setCreatedAt(new \DateTimeImmutable($row['created_at']));
                
                $destinataires[] = $destinataire;
            }
            
            return $destinataires;
        } catch (\Exception $e) {
            $this->logger->error('Erreur recherche par INSEE', [
                'error' => $e->getMessage(),
                'insee' => $insee
            ]);
            return [];
        }
    }
}

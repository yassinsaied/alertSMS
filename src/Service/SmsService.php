<?php

namespace App\Service;

use App\Entity\SmsLog;
use App\Repository\SmsLogRepository;
use Psr\Log\LoggerInterface;

class SmsService
{
    public function __construct(
        private LoggerInterface $logger,
        private SmsLogRepository $smsLogRepository
    ) {
    }

    public function sendSms(string $telephone, string $message, array $metadata = []): bool
    {
        try {
            // Validation basique
            if (empty($telephone) || empty($message)) {
                $this->logger->warning('SMS non envoyé : données manquantes', [
                    'telephone' => $telephone,
                    'message' => $message
                ]);
                
                // CORRECTION : Enregistrer l'échec en base
                $smsLog = new SmsLog(
                    null,
                    $telephone ?: 'telephone_vide',
                    $message ?: 'message_vide',
                    'failed',
                    new \DateTimeImmutable(),
                    json_encode(['error' => 'Données manquantes', 'metadata' => $metadata])
                );
                $this->smsLogRepository->save($smsLog);
                
                return false;
            }

            // Simulation d'envoi (logging)
            $this->logger->info('📱 SMS ENVOYÉ', [
                'telephone' => $telephone,
                'message' => $message,
                'length' => strlen($message),
                'timestamp' => date('Y-m-d H:i:s'),
                'metadata' => $metadata
            ]);

            // Enregistrer le succès en base
            $smsLog = new SmsLog(
                null,
                $telephone,
                $message,
                'sent',
                new \DateTimeImmutable(),
                !empty($metadata) ? json_encode($metadata) : null
            );

            $this->smsLogRepository->save($smsLog);

            // Simulation d'un délai d'envoi
            usleep(100000); // 0.1 seconde

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi SMS', [
                'telephone' => $telephone,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            // Enregistrer l'échec technique
            $smsLog = new SmsLog(
                null,
                $telephone,
                $message,
                'failed',
                new \DateTimeImmutable(),
                json_encode(['error' => $e->getMessage(), 'metadata' => $metadata])
            );

            $this->smsLogRepository->save($smsLog);

            return false;
        }
    }

    public function sendManySms(array $recipients, string $message, array $metadata = []): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($recipients as $telephone) {
            $success = $this->sendSms($telephone, $message, array_merge($metadata, [
                'type' => 'many',
                'batch_size' => count($recipients)
            ]));

            if ($success) {
                $results['sent']++;
                $results['details'][$telephone] = 'sent';
            } else {
                $results['failed']++;
                $results['details'][$telephone] = 'failed';
            }
        }

        $this->logger->info('📱 ENVOI GROUPÉ TERMINÉ', [
            'total_recipients' => count($recipients),
            'sent' => $results['sent'],
            'failed' => $results['failed'],
            'message' => $message
        ]);

        return $results;
    }

    public function getStats(): array
    {
        return $this->smsLogRepository->getStats();
    }
}
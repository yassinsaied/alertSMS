<?php

namespace App\MessageHandler;

use App\Message\AlertMessage;
use App\Service\SmsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AlertMessageHandler
{
    public function __construct(
        private SmsService $smsService,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(AlertMessage $alertMessage): void
    {
        $this->logger->info('DEBUT traitement message alerte', [
            'telephone' => $alertMessage->getTelephone(),
            'insee' => $alertMessage->getInsee()
        ]);

        try {
            $success = $this->smsService->sendSms(
                $alertMessage->getTelephone(),
                $alertMessage->getMessage(),
                array_merge($alertMessage->getMetadata(), [
                    'type' => 'alerte_meteo',
                    'insee' => $alertMessage->getInsee(),
                    'async' => true
                ])
            );

            if ($success) {
                $this->logger->info('SMS alerte envoye avec succes', [
                    'telephone' => $alertMessage->getTelephone(),
                    'insee' => $alertMessage->getInsee()
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur traitement message alerte', [
                'telephone' => $alertMessage->getTelephone(),
                'insee' => $alertMessage->getInsee(),
                'error' => $e->getMessage()
            ]);
            
            throw $e; // Pour retry automatique
        }
    }
}
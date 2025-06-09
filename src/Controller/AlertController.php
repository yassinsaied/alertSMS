<?php

namespace App\Controller;

use App\Message\AlertMessage;  
use App\Repository\DestinataireRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;  
use Symfony\Component\Routing\Attribute\Route;

class AlertController extends AbstractController
{
    public function __construct(
        private DestinataireRepository $destinataireRepository,
        private MessageBusInterface $messageBus,  
        private LoggerInterface $logger
    ) {
    }

    #[Route('/alerter', name: 'app_alerter', methods: ['GET', 'POST'])]
    public function alerter(Request $request): JsonResponse
    {
        // Récupérer le code INSEE (GET ou POST)
        $insee = $request->get('insee') ?? $request->request->get('insee');
        
        if (empty($insee)) {
            return $this->json([
                'success' => false,
                'message' => 'Parametre insee manquant'
            ], 400);
        }

        // Valider le format INSEE
        if (!preg_match('/^\d{5}$/', $insee)) {
            return $this->json([
                'success' => false,
                'message' => 'Format INSEE invalide (5 chiffres attendus)'
            ], 400);
        }

        try {
            // Rechercher les destinataires pour ce code INSEE
            $destinataires = $this->destinataireRepository->findByInsee($insee);

            if (empty($destinataires)) {
                return $this->json([
                    'success' => true,
                    'message' => 'Aucun destinataire trouve pour ce code INSEE',
                    'insee' => $insee,
                    'destinataires_count' => 0
                ]);
            }

            // Message d'alerte météo
            $messageAlerte = "ALERTE METEO : Fortes pluies prevues dans votre region (INSEE: {$insee}). Restez vigilants.";

            // Dispatcher via Messenger (asynchrone)
            $dispatchedCount = 0;
            foreach ($destinataires as $destinataire) {
                $alertMessage = new AlertMessage(
                    $destinataire->getTelephone(),
                    $messageAlerte,
                    $insee,
                    [
                        'campaign' => 'alerte_meteo',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'source' => 'endpoint_alerter'
                    ]
                );

                $this->messageBus->dispatch($alertMessage);
                $dispatchedCount++;
            }

            $this->logger->info('Messages alerte dispatches', [
                'insee' => $insee,
                'destinataires_count' => count($destinataires),
                'messages_dispatched' => $dispatchedCount
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Alertes SMS programmees (asynchrone)',  
                'insee' => $insee,
                'destinataires_count' => count($destinataires),
                'messages_dispatched' => $dispatchedCount  
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur endpoint alerter', [
                'insee' => $insee,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }
}
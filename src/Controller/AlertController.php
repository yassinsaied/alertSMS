<?php

namespace App\Controller;

use App\Message\AlertMessage;
use App\Repository\DestinataireRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class AlertController extends AbstractController
{
    public function __construct(
        private DestinataireRepository $destinataireRepository,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private ParameterBagInterface $parameterBag
    ) {
    }

    #[Route('/alerter', name: 'app_alerter', methods: ['GET', 'POST'])]
    public function alerter(Request $request): JsonResponse
    {
        // Vérification de la clé API
        $apiKeyValidation = $this->validateApiKey($request);
        if ($apiKeyValidation !== null) {
            return $apiKeyValidation;
        }

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

    /**
     * Valide la clé API depuis le header X-API-KEY ou paramètre api_key
     */
    private function validateApiKey(Request $request): ?JsonResponse
    {
        // Récupérer la clé API attendue depuis la configuration
        $expectedApiKey = $this->parameterBag->get('api_key');

        // Récupérer la clé API de la requête (header ou paramètre)
        $providedApiKey = $request->headers->get('X-API-KEY') 
                         ?? $request->get('api_key') 
                         ?? $request->request->get('api_key');

        // Vérifier si la clé API est présente
        if (empty($providedApiKey)) {
            $this->logger->warning('Tentative d\'accès sans clé API', [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent')
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Clé API manquante. Utilisez le header X-API-KEY ou le paramètre api_key.'
            ], 401);
        }

        // Vérifier si la clé API est valide
        if (!hash_equals($expectedApiKey, $providedApiKey)) {
            $this->logger->warning('Tentative d\'accès avec clé API invalide', [
                'ip' => $request->getClientIp(),
                'provided_key' => substr($providedApiKey, 0, 8) . '***',
                'user_agent' => $request->headers->get('User-Agent')
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Clé API invalide.'
            ], 403);
        }

        // Clé API valide, continuer le traitement
        return null;
    }
}
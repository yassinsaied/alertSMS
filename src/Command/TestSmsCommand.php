<?php

namespace App\Command;

use App\Service\SmsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-sms',
    description: 'Tester le service SMS'
)]
class TestSmsCommand extends Command
{
    public function __construct(private SmsService $smsService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Test du Service SMS');

        // Test 1 : SMS simple
        $io->section('Test 1 : Envoi SMS simple');
        $message1 = 'Bonjour, ceci est un message de test !';
        $result1 = $this->smsService->sendSms(
            '0123456789', 
            $message1,
            ['type' => 'test', 'priority' => 'normal']
        );
        
        $details1 = [
            ['Statut', $result1 ? 'ENVOYE' : 'ECHEC'],
            ['Details', '0123456789, ' . date('Y-m-d H:i:s') . ', ' . $message1]
        ];
        
        $io->table(['Resultat', 'Valeur'], $details1);

        // Test 2 : SMS groupe
        $io->section('Test 2 : Envoi SMS groupe');
        $recipients = ['0123456789', '0234567890', '0345678901'];
        $message2 = 'Message alerte groupee';
        $bulkResult = $this->smsService->sendManySms(
            $recipients,
            $message2,
            ['campaign' => 'test_many']
        );
        
        // Tableau de resultats groupes
        $groupResults = [
            ['Total destinataires', count($recipients)],
            ['Envoyes', $bulkResult['sent']],
            ['Echecs', $bulkResult['failed']]
        ];
        
        $io->table(['Metrique', 'Valeur'], $groupResults);
        
        // Details pour chaque SMS
        $io->text('Details par SMS :');
        $detailsTable = [];
        foreach ($bulkResult['details'] as $telephone => $status) {
            $statusText = ($status === 'sent') ? 'ENVOYE' : 'ECHEC';
            $details = $telephone . ', ' . date('Y-m-d H:i:s') . ', ' . $message2;
            $detailsTable[] = [$statusText, $details];
        }
        
        $io->table(['Statut', 'Details (telephone, date, message)'], $detailsTable);

        // Test 3 : SMS avec erreurs multiples
        $io->section('Test 3 : Tests avec erreurs');

        $errorTests = [
            ['', 'Message avec telephone vide'],
            ['0123456789', ''],
            ['', ''],
            ['telephone_invalide', 'Message avec telephone invalide']
        ];

        $errorTable = [];
        foreach ($errorTests as $index => $test) {
            $telephone = $test[0];
            $message = $test[1];
            
            $result = $this->smsService->sendSms($telephone, $message, ['test_error' => $index + 1]);
            
            $statusText = $result ? 'ENVOYE' : 'ECHEC';
            $details = ($telephone ?: 'vide') . ', ' . date('Y-m-d H:i:s') . ', ' . ($message ?: 'vide');
            $errorTable[] = [$statusText, $details];
        }

        $io->table(['Statut', 'Details (telephone, date, message)'], $errorTable);

        $io->success('Tests termines ! Verifiez les logs et la base de donnees.');

        return Command::SUCCESS;
    }
}
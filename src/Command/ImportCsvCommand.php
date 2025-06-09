<?php

namespace App\Command;

use App\Entity\Destinataire;
use App\Repository\DestinataireRepository;
use App\Service\CsvValidatorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-csv',
    description: 'Importer un fichier CSV de destinataires'
)]
class ImportCsvCommand extends Command
{
    public function __construct(
        private DestinataireRepository $destinataireRepository,
        private CsvValidatorService $csvValidator,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Nom du fichier CSV dans data/csv/import/');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fileName = $input->getArgument('file');
        
        // Construire le chemin complet
        $filePath = 'data/csv/import/' . $fileName;
        
        // Vérifier l'existence du fichier
        if (!file_exists($filePath)) {
            $io->error("Le fichier $filePath n'existe pas.");
            $io->note("Placez votre fichier dans : data/csv/import/");
            return Command::FAILURE;
        }

        $io->title('Import CSV des destinataires');
        $io->text("Fichier: $filePath");

        // Initialiser les compteurs
        $successCount = 0;
        $errorCount = 0;
        $lineNumber = 0;
        $errors = [];

        // Traitement du fichier CSV
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Lire le header
            $header = fgetcsv($handle);
            $lineNumber++;
            
            $io->text("Header détecté: " . implode(', ', $header));
            $io->newLine();

            // Barre de progression
            $totalLines = count(file($filePath)) - 1; // -1 pour le header
            $progressBar = $io->createProgressBar($totalLines);
            $progressBar->start();

            while (($data = fgetcsv($handle)) !== false) {
                $lineNumber++;
                $progressBar->advance();
                
                // Vérifier qu'on a au moins 2 colonnes
                if (count($data) < 2) {
                    $errorCount++;
                    $errors[] = "Ligne $lineNumber: Données insuffisantes";
                    continue;
                }

                // Nettoyer les données
                $insee = $this->csvValidator->nettoyerInsee($data[0]);
                $telephone = $this->csvValidator->nettoyerTelephone($data[1]);

                // Validation INSEE
                if (!$this->csvValidator->validateInsee($insee)) {
                    $errorCount++;
                    $errors[] = "Ligne $lineNumber: Code INSEE invalide ($insee)";
                    continue;
                }

                // Validation téléphone
                if (!$this->csvValidator->validateTelephone($telephone)) {
                    $errorCount++;
                    $errors[] = "Ligne $lineNumber: Téléphone invalide ($telephone)";
                    continue;
                }

                // Sauvegarde en base
                $destinataire = new Destinataire(null, $insee, $telephone);
                if ($this->destinataireRepository->save($destinataire)) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Ligne $lineNumber: Erreur sauvegarde en base";
                }
            }

            $progressBar->finish();
            fclose($handle);
        }

        $io->newLine(2);

        // Calcul du taux de réussite
        $totalLines = $successCount + $errorCount;
        $successRate = $totalLines > 0 ? ($successCount / $totalLines) * 100 : 0;

        // Déplacer le fichier selon le résultat
        $destinationPath = $this->moveProcessedFile($filePath, $successRate);

        // Rapport final
        $io->section('Rapport d\'import');
        
        $io->table(
            ['Statistique', 'Nombre'],
            [
                ['Lignes traitées', $totalLines],
                ['Succès', $successCount],
                ['Erreurs', $errorCount],
                ['Taux de réussite', round($successRate, 2) . '%']
            ]
        );

        $io->text("Fichier déplacé vers: $destinationPath");

        // Afficher les erreurs (max 10)
        if (!empty($errors)) {
            $io->section('Détail des erreurs (10 premières)');
            $displayedErrors = array_slice($errors, 0, 10);
            foreach ($displayedErrors as $error) {
                $io->text("• $error");
            }
            
            if (count($errors) > 10) {
                $io->text("... et " . (count($errors) - 10) . " autres erreurs");
            }
        }

        // Message final
        if ($successRate >= 70) {
            $io->success("Import terminé avec succès ! ($successCount enregistrements importés)");
        } else {
            $io->warning("Import terminé mais avec beaucoup d'erreurs. Fichier déplacé vers errors/");
        }

        // Log
        $this->logger->info('Import CSV terminé', [
            'file' => $filePath,
            'success' => $successCount,
            'errors' => $errorCount,
            'success_rate' => $successRate,
            'destination' => $destinationPath
        ]);

        return $successCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function moveProcessedFile(string $originalPath, float $successRate): string
    {
        $fileName = basename($originalPath, '.csv');
        $timestamp = date('Y-m-d_H\hi\ms');
        
        if ($successRate >= 70) {
            // Succès : déplacer vers processed/
            $destinationDir = 'data/csv/processed/';
            $newFileName = "{$fileName}_processed_{$timestamp}.csv";
        } else {
            // Échec : déplacer vers errors/
            $destinationDir = 'data/csv/errors/';
            $newFileName = "{$fileName}_errors_{$timestamp}.csv";
        }
        
        // Créer le répertoire si nécessaire
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }
        
        $destinationPath = $destinationDir . $newFileName;
        
        // Déplacer le fichier
        rename($originalPath, $destinationPath);
        
        return $destinationPath;
    }
}
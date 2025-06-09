<?php

namespace App\Repository;

use App\Entity\SmsLog;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class SmsLogRepository
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger
    ) {
    }

    public function save(SmsLog $smsLog): bool
    {
        try {
            $this->connection->insert('sms_logs', [
                'telephone' => $smsLog->getTelephone(),
                'message' => $smsLog->getMessage(),
                'status' => $smsLog->getStatus(),
                'sent_at' => $smsLog->getSentAt()->format('Y-m-d H:i:s'),
                'metadata' => $smsLog->getMetadata()
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur sauvegarde SMS log', [
                'error' => $e->getMessage(),
                'telephone' => $smsLog->getTelephone()
            ]);
            return false;
        }
    }

    public function getStats(): array
    {
        try {
            $totalSent = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM sms_logs WHERE status = 'sent'"
            );

            $totalFailed = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM sms_logs WHERE status = 'failed'"
            );

            $recentSms = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM sms_logs WHERE sent_at >= NOW() - INTERVAL '24 hours'"
            );

            return [
                'total_sent' => (int) $totalSent,
                'total_failed' => (int) $totalFailed,
                'recent_24h' => (int) $recentSms,
                'success_rate' => $totalSent > 0 ? round(($totalSent / ($totalSent + $totalFailed)) * 100, 2) : 0
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération stats SMS', ['error' => $e->getMessage()]);
            return ['total_sent' => 0, 'total_failed' => 0, 'recent_24h' => 0, 'success_rate' => 0];
        }
    }
}
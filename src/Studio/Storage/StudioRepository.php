<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\Studio\Storage;

use PDO;

final class StudioRepository
{
    private ?PDO $db = null;
    private bool $useDatabaseDriver = false;
    private string $jsonFilePath;

    public function __construct(string $driver = 'sqlite', string $projectDir = '/tmp')
    {
        $this->useDatabaseDriver = $driver === 'database';

        $storageDir = $projectDir . '/var/infile_studio';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }

        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $dbPath = $storageDir . '/fel-studio.sqlite';
            $isNew = !file_exists($dbPath);

            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($isNew) {
                $this->migrateSqlite();
            }
        } else {
            // Fallback to JSON file if SQLite is missing entirely
            $this->jsonFilePath = $storageDir . '/fel-studio.json';
            if (!file_exists($this->jsonFilePath)) {
                file_put_contents($this->jsonFilePath, json_encode([]));
            }
        }
    }

    private function migrateSqlite(): void
    {
        if ($this->db) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS timeline (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    uuid TEXT,
                    serie TEXT,
                    numero TEXT,
                    dte_type TEXT,
                    recipient_tax_id TEXT,
                    idempotency_key TEXT,
                    status TEXT,
                    payload JSON,
                    error_message TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function logTransaction(array $data): void
    {
        $insertData = [
            'uuid' => $data['uuid'] ?? null,
            'serie' => $data['serie'] ?? null,
            'numero' => $data['numero'] ?? null,
            'dte_type' => $data['dte_type'] ?? null,
            'recipient_tax_id' => $data['recipient_tax_id'] ?? null,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'status' => $data['status'] ?? 'issued',
            'payload' => isset($data['payload']) ? json_encode($data['payload']) : null,
            'error_message' => $data['error_message'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->useDatabaseDriver) {
            // Not implemented in standard setup due to missing DBAL hard dependency
            // But we keep the condition to match Laravel
        }

        if ($this->db) {
            $stmt = $this->db->prepare("
                INSERT INTO timeline (
                    uuid, serie, numero, dte_type, recipient_tax_id, 
                    idempotency_key, status, payload, error_message, created_at
                ) VALUES (
                    :uuid, :serie, :numero, :dte_type, :recipient_tax_id,
                    :idempotency_key, :status, :payload, :error_message, :created_at
                )
            ");

            $stmt->execute([
                ':uuid' => $insertData['uuid'],
                ':serie' => $insertData['serie'],
                ':numero' => $insertData['numero'],
                ':dte_type' => $insertData['dte_type'],
                ':recipient_tax_id' => $insertData['recipient_tax_id'],
                ':idempotency_key' => $insertData['idempotency_key'],
                ':status' => $insertData['status'],
                ':payload' => $insertData['payload'],
                ':error_message' => $insertData['error_message'],
                ':created_at' => $insertData['created_at'],
            ]);
        } else {
            // JSON fallback
            $timeline = $this->getJsonTimeline();
            $insertData['id'] = time();
            array_unshift($timeline, $insertData);
            $timeline = array_slice($timeline, 0, 100);
            file_put_contents($this->jsonFilePath, json_encode($timeline));
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTimeline(): array
    {
        if ($this->db) {
            $stmt = $this->db->query("SELECT * FROM timeline ORDER BY created_at DESC LIMIT 100");
            $results = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            foreach ($results as &$row) {
                if (isset($row['payload'])) {
                    $row['payload'] = json_decode((string) $row['payload'], true);
                }
            }

            return $results;
        }

        $results = $this->getJsonTimeline();
        foreach ($results as &$row) {
            if (isset($row['payload']) && is_string($row['payload'])) {
                $row['payload'] = json_decode($row['payload'], true);
            }
        }
        return $results;
    }

    public function clear(): void
    {
        if ($this->db) {
            $this->db->exec("DELETE FROM timeline");
        } else {
            file_put_contents($this->jsonFilePath, json_encode([]));
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getJsonTimeline(): array
    {
        if (!isset($this->jsonFilePath) || !file_exists($this->jsonFilePath)) {
            return [];
        }
        $content = file_get_contents($this->jsonFilePath);
        return $content ? json_decode($content, true) : [];
    }
}

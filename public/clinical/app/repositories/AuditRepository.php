<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Audit Repository
 * Path: public/clinical/app/repositories/AuditRepository.php
 *
 * Responsible only for audit_log database access.
 */

final class AuditRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function record(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_log (
                user_id,
                action,
                entity_type,
                entity_id,
                ip_address,
                user_agent
             ) VALUES (
                :user_id,
                :action,
                :entity_type,
                :entity_id,
                :ip_address,
                :user_agent
             )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function latest(int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));

        $stmt = $this->pdo->prepare(
            'SELECT
                audit_log.id,
                audit_log.user_id,
                audit_log.action,
                audit_log.entity_type,
                audit_log.entity_id,
                audit_log.ip_address,
                audit_log.user_agent,
                audit_log.created_at,
                users.name AS user_name,
                users.email AS user_email
             FROM audit_log
             LEFT JOIN users ON users.id = audit_log.user_id
             ORDER BY audit_log.created_at DESC, audit_log.id DESC
             LIMIT :limit_value'
        );

        $stmt->bindValue('limit_value', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findByEntity(string $entityType, int $entityId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));

        $stmt = $this->pdo->prepare(
            'SELECT
                audit_log.id,
                audit_log.user_id,
                audit_log.action,
                audit_log.entity_type,
                audit_log.entity_id,
                audit_log.ip_address,
                audit_log.user_agent,
                audit_log.created_at,
                users.name AS user_name,
                users.email AS user_email
             FROM audit_log
             LEFT JOIN users ON users.id = audit_log.user_id
             WHERE audit_log.entity_type = :entity_type
               AND audit_log.entity_id = :entity_id
             ORDER BY audit_log.created_at DESC, audit_log.id DESC
             LIMIT :limit_value'
        );

        $stmt->bindValue('entity_type', $entityType, PDO::PARAM_STR);
        $stmt->bindValue('entity_id', $entityId, PDO::PARAM_INT);
        $stmt->bindValue('limit_value', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function search(array $filters = [], int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));

        $sql = '
            SELECT
                audit_log.id,
                audit_log.user_id,
                audit_log.action,
                audit_log.entity_type,
                audit_log.entity_id,
                audit_log.ip_address,
                audit_log.user_agent,
                audit_log.created_at,
                users.name AS user_name,
                users.email AS user_email
            FROM audit_log
            LEFT JOIN users ON users.id = audit_log.user_id
            WHERE 1 = 1
        ';

        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= ' AND audit_log.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= ' AND audit_log.action = :action';
            $params['action'] = (string) $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $sql .= ' AND audit_log.entity_type = :entity_type';
            $params['entity_type'] = (string) $filters['entity_type'];
        }

        if (!empty($filters['entity_id'])) {
            $sql .= ' AND audit_log.entity_id = :entity_id';
            $params['entity_id'] = (int) $filters['entity_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND audit_log.created_at >= :date_from';
            $params['date_from'] = (string) $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND audit_log.created_at <= :date_to';
            $params['date_to'] = (string) $filters['date_to'] . ' 23:59:59';
        }

        $sql .= '
            ORDER BY audit_log.created_at DESC, audit_log.id DESC
            LIMIT :limit_value
        ';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }

        $stmt->bindValue('limit_value', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

<?php

declare(strict_types=1);

/**
 * Promedico Clinical App Audit Service
 * Path: public/clinical/app/services/AuditService.php
 *
 * Responsible for audit workflow and normalising audit data.
 */

final class AuditService
{
    public function __construct(
        private readonly AuditRepository $auditRepository
    ) {}

    public function record(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null
    ): void {
        try {
            $this->auditRepository->record(
                userId: $userId,
                action: $this->normaliseAction($action),
                entityType: $this->normaliseNullableString($entityType),
                entityId: $entityId,
                ipAddress: $this->currentIpAddress(),
                userAgent: $this->currentUserAgent()
            );
        } catch (Throwable $e) {
            /*
             * Audit logging should not break clinical workflows.
             * Later, if needed, add emergency file logging here.
             */
        }
    }

    public function recordLoginSuccess(int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'login_success',
            entityType: 'user',
            entityId: $userId
        );
    }

    public function recordLoginFailed(?int $userId = null): void
    {
        $this->record(
            userId: $userId,
            action: 'login_failed',
            entityType: 'user',
            entityId: $userId
        );
    }

    public function recordLogout(int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'logout',
            entityType: 'user',
            entityId: $userId
        );
    }

    public function recordPatientCreated(int $patientId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'patient_created',
            entityType: 'patient',
            entityId: $patientId
        );
    }

    public function recordPatientViewed(int $patientId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'patient_viewed',
            entityType: 'patient',
            entityId: $patientId
        );
    }

    public function recordPatientUpdated(int $patientId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'patient_updated',
            entityType: 'patient',
            entityId: $patientId
        );
    }

    public function recordPatientDeactivated(int $patientId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'patient_deactivated',
            entityType: 'patient',
            entityId: $patientId
        );
    }

    public function recordTreatmentCreated(int $treatmentId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'treatment_created',
            entityType: 'treatment',
            entityId: $treatmentId
        );
    }

    public function recordTreatmentViewed(int $treatmentId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'treatment_viewed',
            entityType: 'treatment',
            entityId: $treatmentId
        );
    }

    public function recordAddendumCreated(int $addendumId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'addendum_created',
            entityType: 'treatment_addendum',
            entityId: $addendumId
        );
    }

    public function recordAuditLogViewed(int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'audit_log_viewed',
            entityType: 'audit_log',
            entityId: null
        );
    }

    public function latest(int $limit = 100): array
    {
        return $this->auditRepository->latest($limit);
    }

    public function findByEntity(string $entityType, int $entityId, int $limit = 100): array
    {
        return $this->auditRepository->findByEntity(
            entityType: $entityType,
            entityId: $entityId,
            limit: $limit
        );
    }

    public function search(array $filters = [], int $limit = 100): array
    {
        return $this->auditRepository->search(
            filters: $filters,
            limit: $limit
        );
    }

    private function normaliseAction(string $action): string
    {
        $action = strtolower(trim($action));
        $action = preg_replace('/[^a-z0-9_:\-]/', '_', $action);

        return substr($action, 0, 120);
    }

    private function normaliseNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function currentIpAddress(): ?string
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($ipAddress === null) {
            return null;
        }

        return substr((string) $ipAddress, 0, 45);
    }

    private function currentUserAgent(): ?string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if ($userAgent === null) {
            return null;
        }

        return substr((string) $userAgent, 0, 255);
    }

    public function recordFollowUpCompleted(int $treatmentId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'follow_up_completed',
            entityType: 'treatment',
            entityId: $treatmentId
        );
    }

    // Add to existing AuditService class

    public function recordUserCreated(int $targetUserId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'user_created',
            entityType: 'user',
            entityId: $targetUserId
        );
    }

    public function recordUserViewed(int $targetUserId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'user_viewed',
            entityType: 'user',
            entityId: $targetUserId
        );
    }

    public function recordUserUpdated(int $targetUserId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'user_updated',
            entityType: 'user',
            entityId: $targetUserId
        );
    }

    public function recordUserDeactivated(int $targetUserId, int $userId): void
    {
        $this->record(
            userId: $userId,
            action: 'user_deactivated',
            entityType: 'user',
            entityId: $targetUserId
        );
    }
}

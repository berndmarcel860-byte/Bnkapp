<?php
/**
 * BnkApp Admin — Audit Log Model
 */
declare(strict_types=1);

namespace BnkApp\Models;

use BnkApp\Core\Model;

class AuditLog extends Model
{
    protected string $table      = 'audit_logs';
    protected string $primaryKey = 'id';

    /**
     * Audit logs are append-only; no fillable restriction is needed,
     * but direct writes should go through the DB stored procedure or
     * a dedicated service class rather than through this model's create().
     */
    protected array $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id',
        'old_values', 'new_values',
        'ip_address', 'user_agent', 'status', 'failure_reason',
    ];

    /**
     * Create an audit entry (wraps parent to add IP automatically).
     */
    public function log(
        ?int   $userId,
        string $action,
        string $entityType = '',
        string $entityId   = '',
        mixed  $oldValues  = null,
        mixed  $newValues  = null,
        string $status     = 'success',
        string $failReason = ''
    ): int|string {
        return $this->create([
            'user_id'       => $userId,
            'action'        => $action,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'old_values'    => $oldValues !== null ? json_encode($oldValues) : null,
            'new_values'    => $newValues !== null ? json_encode($newValues) : null,
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'status'        => $status,
            'failure_reason'=> $failReason,
        ]);
    }
}

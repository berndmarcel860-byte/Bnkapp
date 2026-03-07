<?php
/**
 * BnkApp Admin — KYC Document Model
 */
declare(strict_types=1);

namespace BnkApp\Models;

use BnkApp\Core\Model;

class KycDocument extends Model
{
    protected string $table      = 'kyc_documents';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id', 'document_type', 'file_path', 'file_hash',
        'mime_type', 'file_size_bytes',
        'status', 'rejection_reason',
        'reviewed_by', 'reviewed_at', 'expiry_date',
    ];
}

-- =============================================================================
-- BnkApp — Migration 05: Email Templates + under_review transaction status
-- =============================================================================

-- ---------------------------------------------------------------------------
-- 1. Add 'under_review' to the transactions.status ENUM
-- ---------------------------------------------------------------------------
ALTER TABLE transactions
    MODIFY COLUMN status ENUM(
        'pending','processing','under_review','completed',
        'failed','cancelled','reversed'
    ) NOT NULL DEFAULT 'pending';

-- ---------------------------------------------------------------------------
-- 2. EMAIL_TEMPLATES
--    Admin-editable HTML email templates with {{variable}} placeholders.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_templates (
    id          INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(100) NOT NULL COMMENT 'Machine-readable key, e.g. transfer_submitted',
    name        VARCHAR(255) NOT NULL COMMENT 'Human-readable template name',
    subject     VARCHAR(255) NOT NULL,
    body_html   LONGTEXT     NOT NULL COMMENT 'HTML body — use {{variable}} placeholders',
    body_text   TEXT                  COMMENT 'Plain-text fallback — auto-generated if NULL',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_templates_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Admin-managed transactional email templates';

-- ---------------------------------------------------------------------------
-- 3. Seed default templates
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO email_templates (slug, name, subject, body_html, body_text) VALUES

-- Transfer submitted (sender)
('transfer_submitted',
 'Transfer Submitted — Sender Notification',
 'Your transfer of {{amount}} has been submitted',
 '<!DOCTYPE html>
<html><body style="font-family:Arial,sans-serif;background:#f4f6fa;margin:0;padding:0;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#1a56db;padding:28px 32px;">
    <h1 style="color:#fff;margin:0;font-size:22px;">🏦 BnkApp</h1>
  </td></tr>
  <tr><td style="padding:32px;">
    <h2 style="color:#1a56db;margin-top:0;">Transfer Submitted</h2>
    <p>Dear <strong>{{customer_name}}</strong>,</p>
    <p>Your transfer of <strong>{{amount}}</strong> to <strong>{{creditor_name}}</strong> has been submitted successfully.</p>
    <table width="100%" cellpadding="8" style="background:#f8faff;border-radius:6px;margin:16px 0;border-collapse:collapse;">
      <tr><td style="color:#6b7280;font-size:13px;">From IBAN</td><td style="font-family:monospace;font-size:13px;">{{from_iban}}</td></tr>
      <tr style="background:#eef2fb;"><td style="color:#6b7280;font-size:13px;">To IBAN</td><td style="font-family:monospace;font-size:13px;">{{to_iban}}</td></tr>
      <tr><td style="color:#6b7280;font-size:13px;">Amount</td><td style="font-size:13px;font-weight:bold;">{{amount}}</td></tr>
      <tr style="background:#eef2fb;"><td style="color:#6b7280;font-size:13px;">Fee</td><td style="font-size:13px;">{{fee}}</td></tr>
      <tr><td style="color:#6b7280;font-size:13px;">Reference</td><td style="font-family:monospace;font-size:12px;">{{reference}}</td></tr>
      <tr style="background:#eef2fb;"><td style="color:#6b7280;font-size:13px;">Status</td><td style="font-size:13px;color:#d97706;font-weight:bold;">{{status}}</td></tr>
    </table>
    <p style="color:#6b7280;font-size:13px;">If you did not initiate this transfer, please contact us immediately.</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">This is an automated message from BnkApp. Please do not reply.</p>
  </td></tr>
</table></td></tr></table>
</body></html>',
 'Dear {{customer_name}},

Your transfer of {{amount}} to {{creditor_name}} has been submitted.

From IBAN : {{from_iban}}
To IBAN   : {{to_iban}}
Amount    : {{amount}}
Fee       : {{fee}}
Reference : {{reference}}
Status    : {{status}}

If you did not initiate this transfer, contact us immediately.'),

-- Transfer under review
('transfer_under_review',
 'Transfer Under Review',
 'Your transfer of {{amount}} is under review',
 '<!DOCTYPE html>
<html><body style="font-family:Arial,sans-serif;background:#f4f6fa;margin:0;padding:0;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#1a56db;padding:28px 32px;">
    <h1 style="color:#fff;margin:0;font-size:22px;">🏦 BnkApp</h1>
  </td></tr>
  <tr><td style="padding:32px;">
    <h2 style="color:#d97706;margin-top:0;">⚠️ Transfer Under Review</h2>
    <p>Dear <strong>{{customer_name}}</strong>,</p>
    <p>Your transfer of <strong>{{amount}}</strong> (reference: <code>{{reference}}</code>) has been placed <strong>under review</strong> by our compliance team.</p>
    <p>{{admin_note}}</p>
    <p>We will notify you once the review is complete. If you have questions, please contact our support team.</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">This is an automated message from BnkApp. Please do not reply.</p>
  </td></tr>
</table></td></tr></table>
</body></html>',
 'Dear {{customer_name}},

Your transfer of {{amount}} (ref: {{reference}}) is under review.

{{admin_note}}

Contact support if you have questions.'),

-- Transfer completed
('transfer_completed',
 'Transfer Completed',
 'Your transfer of {{amount}} has been completed',
 '<!DOCTYPE html>
<html><body style="font-family:Arial,sans-serif;background:#f4f6fa;margin:0;padding:0;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#1a56db;padding:28px 32px;">
    <h1 style="color:#fff;margin:0;font-size:22px;">🏦 BnkApp</h1>
  </td></tr>
  <tr><td style="padding:32px;">
    <h2 style="color:#059669;margin-top:0;">✅ Transfer Completed</h2>
    <p>Dear <strong>{{customer_name}}</strong>,</p>
    <p>Your transfer of <strong>{{amount}}</strong> has been <strong>completed</strong> successfully.</p>
    <table width="100%" cellpadding="8" style="background:#f0fdf4;border-radius:6px;margin:16px 0;border-collapse:collapse;">
      <tr><td style="color:#6b7280;font-size:13px;">Reference</td><td style="font-family:monospace;font-size:12px;">{{reference}}</td></tr>
    </table>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">This is an automated message from BnkApp. Please do not reply.</p>
  </td></tr>
</table></td></tr></table>
</body></html>',
 'Dear {{customer_name}},

Your transfer of {{amount}} (ref: {{reference}}) has been completed.'),

-- Transfer failed
('transfer_failed',
 'Transfer Failed',
 'Your transfer of {{amount}} could not be processed',
 '<!DOCTYPE html>
<html><body style="font-family:Arial,sans-serif;background:#f4f6fa;margin:0;padding:0;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#dc2626;padding:28px 32px;">
    <h1 style="color:#fff;margin:0;font-size:22px;">🏦 BnkApp</h1>
  </td></tr>
  <tr><td style="padding:32px;">
    <h2 style="color:#dc2626;margin-top:0;">❌ Transfer Failed</h2>
    <p>Dear <strong>{{customer_name}}</strong>,</p>
    <p>Unfortunately, your transfer of <strong>{{amount}}</strong> (reference: <code>{{reference}}</code>) could not be processed.</p>
    <p><strong>Reason:</strong> {{failure_reason}}</p>
    <p>Please contact our support team if you need assistance.</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">This is an automated message from BnkApp. Please do not reply.</p>
  </td></tr>
</table></td></tr></table>
</body></html>',
 'Dear {{customer_name}},

Your transfer of {{amount}} (ref: {{reference}}) failed.
Reason: {{failure_reason}}

Contact support for assistance.'),

-- Fee required
('transfer_fee_required',
 'Fee Payment Required for Transfer',
 'Action required: fee payment for your transfer of {{amount}}',
 '<!DOCTYPE html>
<html><body style="font-family:Arial,sans-serif;background:#f4f6fa;margin:0;padding:0;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#1a56db;padding:28px 32px;">
    <h1 style="color:#fff;margin:0;font-size:22px;">🏦 BnkApp</h1>
  </td></tr>
  <tr><td style="padding:32px;">
    <h2 style="color:#d97706;margin-top:0;">💳 Fee Payment Required</h2>
    <p>Dear <strong>{{customer_name}}</strong>,</p>
    <p>A fee of <strong>{{fee}}</strong> is required to process your transfer of <strong>{{amount}}</strong> (reference: <code>{{reference}}</code>).</p>
    <p>{{admin_note}}</p>
    <p>Please log in to your account and make the required fee payment to proceed.</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">This is an automated message from BnkApp. Please do not reply.</p>
  </td></tr>
</table></td></tr></table>
</body></html>',
 'Dear {{customer_name}},

A fee of {{fee}} is required to process your transfer of {{amount}} (ref: {{reference}}).

{{admin_note}}

Please log in to pay the fee.');

<?php
/**
 * View: email-templates/form.php — create & edit
 *
 * $template === null → create mode
 * $template = array  → edit mode
 */
use BnkApp\Helpers\FormatHelper;
$isEdit  = $template !== null;
$action  = $isEdit ? '/email-templates/' . $template['id'] : '/email-templates';
$method  = 'POST';
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/email-templates" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= FormatHelper::e($title) ?></h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= $action ?>">
            <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
            <?php if ($isEdit): ?>
            <input type="hidden" name="_method" value="PATCH">
            <?php endif; ?>

            <div class="row g-3">
                <?php if (!$isEdit): ?>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                    <input type="text" name="slug" class="form-control font-monospace"
                           placeholder="e.g. transfer_submitted"
                           pattern="[a-z0-9_]+"
                           title="Lowercase letters, numbers and underscores only"
                           required>
                    <div class="form-text">Machine-readable key. Must be unique. Cannot be changed after creation.</div>
                </div>
                <?php else: ?>
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted">Slug (read-only)</label>
                    <input type="text" class="form-control font-monospace bg-light" value="<?= FormatHelper::e($template['slug']) ?>" disabled>
                </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= (!$isEdit || $template['is_active']) ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($isEdit && !$template['is_active'])  ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="<?= $isEdit ? FormatHelper::e($template['name']) : '' ?>"
                           maxlength="255" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Subject Line <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control"
                           value="<?= $isEdit ? FormatHelper::e($template['subject']) : '' ?>"
                           maxlength="255" required>
                    <div class="form-text">You may use <code>{{variable}}</code> placeholders in the subject.</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">HTML Body <span class="text-danger">*</span></label>
                    <textarea name="body_html" class="form-control font-monospace" rows="18" required
                              style="font-size:.82rem"><?= $isEdit ? htmlspecialchars($template['body_html'], ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                    <div class="form-text">Full HTML email body. Use <code>{{variable}}</code> for dynamic content.</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Plain-Text Fallback</label>
                    <textarea name="body_text" class="form-control font-monospace" rows="6"
                              style="font-size:.82rem"><?= $isEdit ? htmlspecialchars((string)($template['body_text'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                    <div class="form-text">Leave blank to auto-generate from HTML.</div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Available placeholders depend on the template event.</strong>
                        Common ones: <code>{{customer_name}}</code>, <code>{{amount}}</code>, <code>{{fee}}</code>,
                        <code>{{from_iban}}</code>, <code>{{to_iban}}</code>, <code>{{creditor_name}}</code>,
                        <code>{{reference}}</code>, <code>{{status}}</code>, <code>{{admin_note}}</code>,
                        <code>{{failure_reason}}</code>.
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $isEdit ? 'floppy' : 'plus-circle' ?> me-1"></i>
                        <?= $isEdit ? 'Save Changes' : 'Create Template' ?>
                    </button>
                    <a href="/email-templates" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';

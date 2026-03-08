<?php
/**
 * View: email-templates/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title mb-0">Email Templates</h1>
        <p class="text-muted small mb-0">Manage transactional email templates. Use <code>{{variable}}</code> for placeholders.</p>
    </div>
    <a href="/email-templates/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>New Template
    </a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table admin-table" id="tpl-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Slug</th>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($templates)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No email templates found.</td></tr>
            <?php else: ?>
                <?php foreach ($templates as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td><code class="small"><?= FormatHelper::e($t['slug']) ?></code></td>
                    <td><?= FormatHelper::e($t['name']) ?></td>
                    <td class="small text-muted"><?= FormatHelper::e(FormatHelper::truncate($t['subject'], 60)) ?></td>
                    <td>
                        <?php if ($t['is_active']): ?>
                            <span class="badge text-bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= FormatHelper::dateTime($t['updated_at']) ?></td>
                    <td class="text-end">
                        <a href="/email-templates/<?= $t['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="/email-templates/<?= $t['id'] ?>" class="d-inline"
                              data-confirm="Delete this template permanently?">
                            <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';

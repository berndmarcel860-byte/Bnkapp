<?php
/**
 * BnkApp Admin — Email Template Controller
 *
 * CRUD for the email_templates table.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;
use BnkApp\Middleware\CsrfMiddleware;

class EmailTemplateController extends Controller
{
    /**
     * GET /email-templates
     */
    public function index(Request $request, array $params = []): void
    {
        $db        = Database::getInstance();
        $templates = $db->query("SELECT * FROM email_templates ORDER BY slug")->fetchAll();

        $this->view('email-templates.index', [
            'title'     => 'Email Templates',
            'templates' => $templates,
        ]);
    }

    /**
     * GET /email-templates/create
     */
    public function create(Request $request, array $params = []): void
    {
        $this->view('email-templates.form', [
            'title'    => 'New Email Template',
            'template' => null,
        ]);
    }

    /**
     * POST /email-templates
     */
    public function store(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);

        $errors = $this->validate($request, [
            'slug'      => 'required|max:100',
            'name'      => 'required|max:255',
            'subject'   => 'required|max:255',
            'body_html' => 'required',
        ]);

        if (!empty($errors)) {
            $this->flashError('Please fill in all required fields.');
            $this->redirect('/email-templates/create');
        }

        $db = Database::getInstance();

        // Check slug uniqueness
        $exists = $db->prepare("SELECT id FROM email_templates WHERE slug = ? LIMIT 1");
        $exists->execute([$request->input('slug')]);
        if ($exists->fetch()) {
            $this->flashError('A template with that slug already exists.');
            $this->redirect('/email-templates/create');
        }

        $db->prepare(
            "INSERT INTO email_templates (slug, name, subject, body_html, body_text, is_active)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            strtolower(trim((string)$request->input('slug'))),
            $request->input('name'),
            $request->input('subject'),
            $request->input('body_html'),
            $request->input('body_text') ?: null,
            $request->input('is_active') === '1' ? 1 : 0,
        ]);

        $this->flashSuccess('Email template created.');
        $this->redirect('/email-templates');
    }

    /**
     * GET /email-templates/{id}/edit
     */
    public function edit(Request $request, array $params = []): void
    {
        $template = $this->findOrAbort((int)$params['id']);

        $this->view('email-templates.form', [
            'title'    => 'Edit Email Template — ' . $template['name'],
            'template' => $template,
        ]);
    }

    /**
     * PATCH /email-templates/{id}
     */
    public function update(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $template = $this->findOrAbort((int)$params['id']);

        $errors = $this->validate($request, [
            'name'      => 'required|max:255',
            'subject'   => 'required|max:255',
            'body_html' => 'required',
        ]);

        if (!empty($errors)) {
            $this->flashError('Please fill in all required fields.');
            $this->redirect("/email-templates/{$params['id']}/edit");
        }

        Database::getInstance()->prepare(
            "UPDATE email_templates
                SET name = ?, subject = ?, body_html = ?, body_text = ?, is_active = ?
              WHERE id = ?"
        )->execute([
            $request->input('name'),
            $request->input('subject'),
            $request->input('body_html'),
            $request->input('body_text') ?: null,
            $request->input('is_active') === '1' ? 1 : 0,
            $template['id'],
        ]);

        $this->flashSuccess('Email template updated.');
        $this->redirect('/email-templates');
    }

    /**
     * DELETE /email-templates/{id}
     */
    public function destroy(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $template = $this->findOrAbort((int)$params['id']);

        Database::getInstance()->prepare("DELETE FROM email_templates WHERE id = ?")->execute([$template['id']]);

        $this->flashSuccess('Email template deleted.');
        $this->redirect('/email-templates');
    }

    // ------------------------------------------------------------------

    private function findOrAbort(int $id): array
    {
        $stmt = Database::getInstance()->prepare("SELECT * FROM email_templates WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->abort(HTTP_NOT_FOUND, 'Email template not found.');
        }
        return $row;
    }
}

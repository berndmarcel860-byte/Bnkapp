<?php
/**
 * BnkApp Admin — Report Controller
 *
 * Management reports: transaction volume, loan portfolio, audit log.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class ReportController extends Controller
{
    /**
     * GET /reports
     * Summary landing page.
     */
    public function index(Request $request, array $params = []): void
    {
        $this->view('reports.index', ['title' => 'Reports']);
    }

    /**
     * GET /reports/transactions
     */
    public function transactions(Request $request, array $params = []): void
    {
        $from = $request->query('from', date('Y-m-01'));
        $to   = $request->query('to',   date('Y-m-d'));

        $db   = Database::getInstance();
        $data = $db->prepare(
            "SELECT
                 transaction_type,
                 currency_code,
                 COUNT(*)        AS count,
                 SUM(amount)     AS total_amount,
                 SUM(fee_amount) AS total_fees,
                 AVG(amount)     AS avg_amount
               FROM transactions
              WHERE DATE(created_at) BETWEEN ? AND ?
                AND status = 'completed'
              GROUP BY transaction_type, currency_code
              ORDER BY total_amount DESC"
        );
        $data->execute([$from, $to]);

        $this->view('reports.transactions', [
            'title'  => 'Transaction Report',
            'rows'   => $data->fetchAll(),
            'from'   => $from,
            'to'     => $to,
        ]);
    }

    /**
     * GET /reports/loans
     */
    public function loans(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $data = $db->query(
            "SELECT
                 loan_type,
                 status,
                 COUNT(*)              AS count,
                 SUM(principal_amount) AS total_principal,
                 SUM(outstanding_balance) AS total_outstanding,
                 AVG(interest_rate)    AS avg_rate
               FROM loans
              GROUP BY loan_type, status
              ORDER BY total_principal DESC"
        );

        $this->view('reports.loans', [
            'title' => 'Loan Portfolio Report',
            'rows'  => $data->fetchAll(),
        ]);
    }

    /**
     * GET /reports/audit
     */
    public function audit(Request $request, array $params = []): void
    {
        $page   = (int)($request->query('page', 1));
        $action = $request->query('action', '');
        $from   = $request->query('from', date('Y-m-d'));
        $to     = $request->query('to',   date('Y-m-d'));

        $db     = Database::getInstance();
        $offset = ($page - 1) * 50;

        $where    = 'DATE(al.created_at) BETWEEN ? AND ?';
        $bindings = [$from, $to];

        if ($action !== '') {
            $where    .= ' AND al.action = ?';
            $bindings[] = $action;
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs al WHERE {$where}");
        $countStmt->execute($bindings);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) AS admin_name
               FROM audit_logs al
               LEFT JOIN users u ON u.id = al.user_id
              WHERE {$where}
              ORDER BY al.created_at DESC
              LIMIT 50 OFFSET {$offset}"
        );
        $stmt->execute($bindings);

        $this->view('reports.audit', [
            'title'  => 'Audit Log',
            'rows'   => $stmt->fetchAll(),
            'filter' => compact('action', 'from', 'to'),
            'page'   => $page,
        ]);
    }
}

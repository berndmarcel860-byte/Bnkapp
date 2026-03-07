<?php
/**
 * BnkApp Admin — Base Model
 *
 * All admin models extend this class.
 * Provides a shared PDO connection and common CRUD helpers so that
 * concrete models only need to define their table name and column list.
 */
declare(strict_types=1);

namespace BnkApp\Core;

use PDO;
use PDOStatement;

abstract class Model
{
    /** The database table this model represents. Override in subclasses. */
    protected string $table = '';

    /** Primary key column name. */
    protected string $primaryKey = 'id';

    /** Columns that may be mass-assigned via create() / update(). */
    protected array $fillable = [];

    /** Columns that are never returned in query results. */
    protected array $hidden = ['password_hash', 'password_salt', 'two_factor_secret'];

    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ------------------------------------------------------------------
    // Retrieval
    // ------------------------------------------------------------------

    /**
     * Find a single row by primary key. Returns null if not found.
     */
    public function find(int|string $id): ?array
    {
        $sql  = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1";
        $stmt = $this->query($sql, [$id]);
        $row  = $stmt->fetch();
        return $row !== false ? $this->hideColumns($row) : null;
    }

    /**
     * Find a single row matching a WHERE condition.
     */
    public function findBy(string $column, mixed $value): ?array
    {
        $sql  = "SELECT * FROM `{$this->table}` WHERE `{$column}` = ? LIMIT 1";
        $stmt = $this->query($sql, [$value]);
        $row  = $stmt->fetch();
        return $row !== false ? $this->hideColumns($row) : null;
    }

    /**
     * Return all rows in the table.
     */
    public function all(string $orderBy = '', string $direction = 'ASC'): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy !== '') {
            $sql .= " ORDER BY `{$orderBy}` {$direction}";
        }
        return array_map([$this, 'hideColumns'], $this->query($sql)->fetchAll());
    }

    /**
     * Paginate rows.
     *
     * @return array{data: array, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 25, string $where = '', array $bindings = []): array
    {
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;
        $where   = $where ? "WHERE {$where}" : '';

        $total = (int)$this->query(
            "SELECT COUNT(*) FROM `{$this->table}` {$where}",
            $bindings
        )->fetchColumn();

        $rows = $this->query(
            "SELECT * FROM `{$this->table}` {$where} LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        )->fetchAll();

        return [
            'data'      => array_map([$this, 'hideColumns'], $rows),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    // ------------------------------------------------------------------
    // Write
    // ------------------------------------------------------------------

    /**
     * Insert a new row. Returns the new primary key value.
     *
     * @param array $data Column => value pairs (filtered to $fillable)
     */
    public function create(array $data): int|string
    {
        $data    = $this->filterFillable($data);
        $columns = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->query(
            "INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        return $this->db->lastInsertId();
    }

    /**
     * Update a row by primary key. Returns the number of affected rows.
     */
    public function update(int|string $id, array $data): int
    {
        $data = $this->filterFillable($data);
        if (empty($data)) {
            return 0;
        }

        $set  = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
        $stmt = $this->query(
            "UPDATE `{$this->table}` SET {$set} WHERE `{$this->primaryKey}` = ?",
            [...array_values($data), $id]
        );

        return $stmt->rowCount();
    }

    /**
     * Delete a row by primary key. Returns the number of affected rows.
     */
    public function delete(int|string $id): int
    {
        $stmt = $this->query(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
        return $stmt->rowCount();
    }

    // ------------------------------------------------------------------
    // Raw query helpers
    // ------------------------------------------------------------------

    /**
     * Execute a parameterised query and return the PDOStatement.
     */
    protected function query(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data; // no restriction
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    private function hideColumns(array $row): array
    {
        foreach ($this->hidden as $col) {
            unset($row[$col]);
        }
        return $row;
    }
}

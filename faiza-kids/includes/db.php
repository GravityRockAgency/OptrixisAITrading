<?php
/**
 * Faiza Kids Concierge - Database Connection & Helpers
 * PDO-based database layer with utility functions
 */

if (!defined('DB_HOST')) {
    $config_path = dirname(__DIR__) . '/config.php';
    if (file_exists($config_path)) {
        require_once $config_path;
    } else {
        die('Configuration file not found. Please run the installer.');
    }
}

// Global PDO instance
$pdo = null;

/**
 * Get or create the PDO connection
 */
function get_pdo(): PDO {
    global $pdo;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (defined('APP_ENV') && APP_ENV === 'development') {
                die('Database connection failed: ' . $e->getMessage());
            } else {
                error_log('Database connection failed: ' . $e->getMessage());
                die(json_encode(['success' => false, 'error' => 'Database connection error.']));
            }
        }
    }
    return $pdo;
}

// Initialize connection immediately
try {
    $pdo = get_pdo();
} catch (Exception $e) {
    error_log('DB init error: ' . $e->getMessage());
}

/**
 * Execute a query and return the PDOStatement
 *
 * @param string $sql
 * @param array  $params
 * @return PDOStatement
 */
function db_query(string $sql, array $params = []): PDOStatement {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch a single row
 *
 * @param string $sql
 * @param array  $params
 * @return array|null
 */
function db_fetch(string $sql, array $params = []): ?array {
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/**
 * Fetch all rows
 *
 * @param string $sql
 * @param array  $params
 * @return array
 */
function db_fetch_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

/**
 * Insert a row into a table
 *
 * @param string $table
 * @param array  $data  associative array of column => value
 * @return int  Last inserted ID
 */
function db_insert(string $table, array $data): int {
    $pdo     = get_pdo();
    $columns = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $sql  = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    return (int) $pdo->lastInsertId();
}

/**
 * Update rows in a table
 *
 * @param string $table
 * @param array  $data       associative array of column => value to set
 * @param array  $where      associative array of column => value for WHERE clause (AND-joined)
 * @return int   Number of affected rows
 */
function db_update(string $table, array $data, array $where): int {
    $set_parts   = array_map(fn($c) => "`$c` = ?", array_keys($data));
    $where_parts = array_map(fn($c) => "`$c` = ?", array_keys($where));
    $sql = "UPDATE `$table` SET " . implode(', ', $set_parts)
         . " WHERE " . implode(' AND ', $where_parts);
    $params = array_merge(array_values($data), array_values($where));
    return db_query($sql, $params)->rowCount();
}

/**
 * Delete rows from a table by a single column condition
 *
 * @param string $table
 * @param string $where_col
 * @param mixed  $where_val
 * @return int   Number of affected rows
 */
function db_delete(string $table, string $where_col, $where_val): int {
    $sql = "DELETE FROM `$table` WHERE `$where_col` = ?";
    return db_query($sql, [$where_val])->rowCount();
}

/**
 * Count rows matching a condition
 *
 * @param string $table
 * @param array  $where   optional associative array for WHERE
 * @return int
 */
function db_count(string $table, array $where = []): int {
    $sql = "SELECT COUNT(*) FROM `$table`";
    $params = [];
    if (!empty($where)) {
        $parts = array_map(fn($c) => "`$c` = ?", array_keys($where));
        $sql .= " WHERE " . implode(' AND ', $parts);
        $params = array_values($where);
    }
    return (int) db_query($sql, $params)->fetchColumn();
}

/**
 * Begin a transaction
 */
function db_begin(): void {
    get_pdo()->beginTransaction();
}

/**
 * Commit a transaction
 */
function db_commit(): void {
    get_pdo()->commit();
}

/**
 * Roll back a transaction
 */
function db_rollback(): void {
    get_pdo()->rollBack();
}

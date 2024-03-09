<?php
namespace Bibek8366\MyPhpApp\Helpers;
use PDO;
use PDOException;
use Exception;
class DbUtils {
    /**
     * Create a PDO connection to the database.
     *
     * @param string $db_host Database host
     * @param string $db_user Database username
     * @param string $db_pass Database password
     * @param string $db_name Database name
     * @param bool $isPersistent Whether to use persistent connection
     * @return PDO PDO connection object
     * @throws Exception If connection fails
     */
    public function createConnection(
        string $db_host,
        string $db_user,
        string $db_pass,
        string $db_name,
        bool $isPersistent
    ): PDO {
        try {
            $dsn = "mysql:host=$db_host;dbname=$db_name";
            $username = $db_user;
            $password = $db_pass;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => $isPersistent
            ];
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Error creating PDO connection: " . $e->getMessage());
            throw new Exception("Error creating PDO connection: " . $e->getMessage());
        }
    }

    /**
     * Execute a SQL query with optional parameters.
     *
     * @param PDO $pdo PDO connection object
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array|int Result set or number of affected rows
     * @throws Exception If query execution fails
     */
    public function executeQuery(PDO $pdo, string $sql, array $params = []): array|int {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if (stripos($sql, 'SELECT') === 0) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error executing SQL query: " . $e->getMessage());
            throw new Exception("Error executing SQL query: " . $e->getMessage());
        }
    }

    // PAGINATION HELPER METHODS -----------------------------------------------

    /**
     * Calculate the OFFSET value for pagination.
     *
     * @param int $page Page number
     * @param int $limit Number of items per page
     * @return int Offset value
     */
    public function getOffset(int $page, int $limit): int {
        return ($page - 1) * $limit;
    }

    public function getTotalRowsCount($pdo, $table): int {
    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
    return (int) $stmt->fetchColumn();
    }

    public function getFilteredRowsCount($pdo, $table, $sql, $params): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $sql");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
    }

    /**
     * Get the total number of pages for pagination.
     *
     * @param int $totalItems Total number of items (rows in a table)
     * @param int $limit Number of items per page
     * @return int Total number of pages
     */
    public function getTotalPages(int $totalItems, int $limit): int {
        return (int) ceil($totalItems / $limit);
    }

    // Model FACTORY METHOD --------------------------------------------
    public function model(PDO $pdo, string $table): Model {
        return new Model($table, $pdo);
    }

}

// Model class
/*
* (Model class is used to perform CRUD operations on a database table)
*/
class Model {
    protected PDO $pdo;
    protected string $table;

    public function __construct($pdo, $table) {
        $this->table = $table;
        $this->pdo = $pdo;
    }

    public function insert(array $columns, array $values): int {
        try {
            $columns = implode(', ', $columns);
            $placeholders = rtrim(str_repeat('?, ', count($values)), ', ');
            $stmt = $this->pdo->prepare("INSERT INTO $this->table ($columns) VALUES ($placeholders)");
            $stmt->execute($values);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error inserting record: " . $e->getMessage());
            throw new Exception("Error inserting record: " . $e->getMessage());
        }
    }

    public function select(
        array $selectColumns = [],
        array $whereColumns = [],
        array $whereValues = []
        ): array {
        try {
            $selectColumns = empty($selectColumns) ? '*' : implode(', ', $selectColumns);
            $whereColumnsWithPlaceholders = empty($whereColumns) ? '' : implode(' = ? AND ', $whereColumns) . ' = ?';
            $whereQuery = empty($whereColumnsWithPlaceholders) ? '' : "WHERE $whereColumnsWithPlaceholders";
            $whereValues = empty($whereValues) ? [] : $whereValues;
            // prepare and execute the query
            $stmt = $this->pdo->prepare("SELECT $selectColumns FROM $this->table $whereQuery");
            $stmt->execute($whereValues);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            error_log("Error selecting records: " . $e->getMessage());
            throw new Exception("Error selecting records: " . $e->getMessage());
        }
    }

    public function update(
        array $updateColumns,
        array $updateValues,
        array $whereColumns = [],
        $whereValues = []
        ): int {
        try {
            $updateColumnsWithPlaceholders = implode(' = ?, ', $updateColumns) . ' = ?';
            $whereColumnsWithPlaceholders = empty($whereColumns) ? '' : implode(' = ? AND ', $whereColumns) . ' = ?';
            $whereQuery = empty($whereColumnsWithPlaceholders) ? '' : "WHERE $whereColumnsWithPlaceholders";
            $whereValues = empty($whereValues) ? [] : $whereValues;
            $params = array_merge($updateValues, $whereValues);
            // prepare and execute the query
            $stmt = $this->pdo->prepare("UPDATE $this->table SET $updateColumnsWithPlaceholders $whereQuery");
            $stmt->execute($params);
            return $stmt->rowCount();
        }
        catch (PDOException $e) {
            error_log("Error updating record: " . $e->getMessage());
            throw new Exception("Error updating record: " . $e->getMessage());
        }
    }

    public function delete(array $whereColumns = [], array $whereValues = []): int {
      try {
        $whereColumnsWithPlaceholders = empty($whereColumns) ? '' : implode(' = ? AND ', $whereColumns) . ' = ?';
        $whereQuery = empty($whereColumnsWithPlaceholders) ? '' : "WHERE $whereColumnsWithPlaceholders";
        $whereValues = empty($whereValues) ? [] : $whereValues;
        // prepare and execute the query
        $stmt = $this->pdo->prepare("DELETE FROM $this->table $whereQuery");
        $stmt->execute($whereValues);
        return $stmt->rowCount();
      }
      catch (PDOException $e) {
        error_log("Error deleting record: " . $e->getMessage());
        throw new Exception("Error deleting record: " . $e->getMessage());
      }
    }
    public function filterArrayIncluding(array $array, array $fieldNames): array {
        $included = [];
        foreach ($fieldNames as $fieldName) {
            $included[$fieldName] = $array[$fieldName];
        }
        return $included;
    }

    public function filterArrayExcluding(array $array, array $fieldNames): array {
        $excluded = $array;
        foreach ($fieldNames as $fieldName) {
            unset($excluded[$fieldName]);
        }
        return $excluded;
    }
    
}




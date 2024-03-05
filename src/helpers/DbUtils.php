<?php
namespace MyPhpApp\Helpers;
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
        bool   $isPersistent
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
            // Log the error instead of re-throwing immediately
            error_log("Error creating PDO connection: " . $e->getMessage());
            throw new Exception("Database connection failed");
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
            throw new Exception("Query execution failed");
        }
    }

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

}



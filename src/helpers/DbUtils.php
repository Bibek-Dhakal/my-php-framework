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
        return new Model($pdo, $table);
    }

}

// Model class
/*
* (Model class is used to perform CRUD operations on a database table)
*/
class Model {
    protected PDO $pdo;
    protected string $table;

    public function __construct(PDO $pdo, string $table) {
        $this->table = $table;
        $this->pdo = $pdo;
    }

/**
 * Inserts multiple rows into the database table.
 * @method insert
 * @param array $columns              An array containing the column names.
 * @param array $multipleArrayOfValues An array of arrays, where each inner array represents
 *                                    the values for a single row to be inserted.
 * @return int                        The last inserted ID.
 * @throws Exception                  If an error occurs during the insertion process.
 */
    public function insert(array $columns, array $multipleArrayOfValues): int {
      try {
        // Count the number of columns
        $columnCount = count($columns);
        
        // Convert column names into a comma-separated string
        $columns = implode(', ', $columns);
        
        // Generate placeholders for the values in each row
        $placeholders = rtrim(str_repeat('(' . rtrim(str_repeat('?, ', $columnCount), ', ') . '), ', count($multipleArrayOfValues)), ', ');
        
        // Flatten the array of arrays into a single array of values
        $flattenedValues = [];
        foreach ($multipleArrayOfValues as $values) {
            $flattenedValues = array_merge($flattenedValues, $values);
        }

        // Prepare and execute the INSERT INTO statement
        $stmt = $this->pdo->prepare("INSERT INTO $this->table ($columns) VALUES $placeholders");
        $stmt->execute($flattenedValues);
        
        // Retrieve the last inserted ID
        $index = $this->pdo->lastInsertId();
        
        // Check if the last inserted ID is valid
        if ($index === false) {
            throw new Exception('Error inserting record');
        }
        
        // Return the last inserted ID
        return (int) $index;
      } catch (PDOException $e) {
        // Log and rethrow any caught PDO exceptions
        error_log("Error inserting record: " . $e->getMessage());
        throw new Exception("Error inserting record: " . $e->getMessage());
      }
    }

    // Function to insert a record into a table
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

    // Function to update records in a table
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
            $noOfRowsAffected = $stmt->rowCount();
            if($noOfRowsAffected == 0) {
                throw new Exception('Error updating record');
            }
            return (int) $noOfRowsAffected;
        }
        catch (PDOException $e) {
            error_log("Error updating record: " . $e->getMessage());
            throw new Exception("Error updating record: " . $e->getMessage());
        }
    }

    // Function to delete records from a table
    public function delete(array $whereColumns = [], array $whereValues = []): int {
      try {
        $whereColumnsWithPlaceholders = empty($whereColumns) ? '' : implode(' = ? AND ', $whereColumns) . ' = ?';
        $whereQuery = empty($whereColumnsWithPlaceholders) ? '' : "WHERE $whereColumnsWithPlaceholders";
        $whereValues = empty($whereValues) ? [] : $whereValues;
        // prepare and execute the query
        $stmt = $this->pdo->prepare("DELETE FROM $this->table $whereQuery");
        $stmt->execute($whereValues);
        $noOfRowsAffected = $stmt->rowCount();
        if($noOfRowsAffected == 0) {
            throw new Exception('Error deleting record');
        }
        return (int) $noOfRowsAffected;
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

    // Function to check if a row exists in a table based on given column-value pairs
    public function checkARowExistsByColumns(array $columns, array $values) {
        if(count($columns) !== count($values)) {
            throw new Exception("Number of columns does not match number of values");
        }

        $query = "SELECT COUNT(*) FROM $this->table WHERE ";
        foreach ($columns as $column) {
            $query .= "$column = ? AND ";
        }
        $query = rtrim($query, "AND ");

        $statement = $this->pdo->prepare($query);

        // Bind values
        for ($i = 0; $i < count($values); $i++) {
            $statement->bindValue($i + 1, $values[$i]);
        }

        $statement->execute();
        $count = $statement->fetchColumn();

        return $count > 0;
    }

    // Function to get distinct values for given columns from a table
    public function getDistinctValues(array $columns) {
        $query = "SELECT DISTINCT ";
        foreach ($columns as $column) {
            $query .= "$column, ";
        }
        $query = rtrim($query, ", ");
        $query .= " FROM $this->table";

        $statement = $this->pdo->query($query);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get a page of records from a table with offset and limit
    public function getPage($offset, $limit) {
        $query = "SELECT * FROM $this->table LIMIT $offset, $limit";

        $statement = $this->pdo->query($query);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get the total number of rows in a table
    public function getNoOfRows() {
        $query = "SELECT COUNT(*) FROM $this->table";

        $statement = $this->pdo->query($query);

        return $statement->fetchColumn();
    }

    // Function to get the total number of rows in a table based on given column-value pairs
    public function getNoOfRowsByColumns(array $columns, array $values) {
        if(count($columns) !== count($values)) {
            throw new Exception("Number of columns does not match number of values");
        }

        $query = "SELECT COUNT(*) FROM $this->table WHERE ";
        foreach ($columns as $column) {
            $query .= "$column = ? AND ";
        }
        $query = rtrim($query, "AND ");

        $statement = $this->pdo->prepare($query);

        // Bind values
        for ($i = 0; $i < count($values); $i++) {
            $statement->bindValue($i + 1, $values[$i]);
        }

        $statement->execute();
        return $statement->fetchColumn();
    }
    
}




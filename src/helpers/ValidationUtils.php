<?php
namespace MyPhpApp\Helpers;
use Exception;

/**
 * Utility class for handling validation operations.
 */
class ValidationUtils {

    /**
     * Create a new schema for defining validation rules.
     *
     * @return Schema  A new instance of the Schema class.
     */
    public function createSchema(): Schema {
        return new Schema();
    }

    /**
     * Validate data against a given schema.
     *
     * @param array $data    The data to validate.
     * @param Schema $schema The schema containing validation rules.
     * @throws Exception     If validation fails.
     */
    public function validateData($data, $schema): void {
        try {
            $validator = new Validator();
            $validator->validate($data, $schema);
        } catch (ValidationError $e) {
            throw new Exception("Validation error: " . json_encode($e->errors));
        }
    }

    /**
     * Middleware function for validating request data against a schema.
     *
     * @param array $body     The request body data.
     * @param Schema $schema  The schema containing validation rules.
     * @param callable $next The next middleware function.
     * @throws Exception     If validation fails.
     */
    public function validateRequestData(array $body, Schema $schema, callable $next): void {
        try {
            $validator = new Validator();
            $validator->validate($body, $schema);
            $next();
        } catch (ValidationError $e) {
            $errors = $e->errors;
            next($errors);
        }
    }
}

/**
 * Represents a schema containing validation rules for fields.
 */
class Schema {
    protected array $fields = [];

    /**
     * Define validation rules for a field.
     *
     * @param string $fieldName    The name of the field.
     * @param array $constraints   Validation constraints for the field.
     */
    public function field($fieldName, $constraints): void {
        $this->fields[$fieldName] = $constraints;
    }

    /**
     * Pick specific fields from the schema.
     *
     * @param string ...$fields   The names of fields to pick.
     * @return Schema             A new schema containing the picked fields.
     */
    public function pick(...$fields): Schema {
        $pickedSchema = new self();
        foreach ($fields as $field) {
            if (isset($this->fields[$field])) {
                $pickedSchema->field($field, $this->fields[$field]);
            }
        }
        return $pickedSchema;
    }

    /**
     * Get all fields and their validation constraints.
     *
     * @return array  An array of fields and their constraints.
     */
    public function getFields(): array {
        return $this->fields;
    }
}

/**
 * Validates data against a given schema.
 */
class Validator {
    public function validate($data, Schema $schema): void {
        $errors = [];
        foreach ($schema->getFields() as $field => $constraints) {
            if (!isset($data[$field])) {
                if (isset($constraints['required']) && $constraints['required'] === true) {
                    $errors[$field][] = $constraints['errorMessage'] ?? "Field '$field' is required.";
                }
            } else {
                // Validate field based on its constraints
                $value = $data[$field];
                if (isset($constraints['minlength']) && strlen($value) < $constraints['minlength']) {
                    $errors[$field][] = $constraints['minLengthErrorMessage']
                        ?? "Field '$field' must have at least {$constraints['minlength']} characters.";
                }
                // Additional validation rules can be added here...
            }
        }
        if (!empty($errors)) {
            throw new ValidationError($errors);
        }
    }
}

/**
 * Represents an error that occurs during validation.
 */
Class ValidationError extends Exception {
    public array $errors;

    /**
     * ValidationError constructor.
     *
     * @param array $errors  An array containing validation errors.
     */
    public function __construct(array $errors) {
        parent::__construct('Validation_Error');
        $this->errors = $errors;
    }
}



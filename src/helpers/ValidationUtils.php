<?php
namespace Bibek8366\MyPhpApp\Helpers;
use Exception;
/**
 * ValidationUtils class provides utility methods for creating schemas and validating data against them.
 */
class ValidationUtils {

    /**
     * Create a new field instance.
     * @method createField
     * @param string $name The name of the field.
     * @return Field A new instance of the Field class.
     */
    public function createField($name): Field {
        return new Field($name);
    }
    
    /**
     * Create a new schema instance.
     * @method createSchema
     * @return Schema A new instance of the Schema class.
     */
    public function createSchema(): Schema {
        return new Schema();
    }

    /**
     * Validate data against the given schema.
     * @method validateData
     * @param array $data The data to validate.
     * @param Schema $schema The schema containing validation rules.
     * @throws Exception If validation fails.
     */
    public function validateData($data, $schema): void {
        try {
            $validator = new Validator();
            $validator->validate($data, $schema);
        } catch (ValidationError $e) {
            error_log("Validation error: " . json_encode($e->errors)); // Log error
            throw new Exception("Validation error: " . json_encode($e->errors));
        }
    }

    /**
     * Middleware function for validating request data against a schema.
     * @method validateRequestData
     * @param array $body The request body data.
     * @param Schema $schema The schema containing validation rules.
     * @param callable $next The next middleware function.
     * @throws Exception If validation fails.
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
 * Schema class represents a schema containing validation rules for fields.
 */
class Schema {
    protected array $fields = [];

    /**
     * Add a field to the schema.
     * @method addField
     * @param Field $field The field to add.
     */
    public function addField(Field $field): void {
        $this->fields[$field->getName()] = $field;
    }

    /**
     * Get all fields and their validation constraints.
     * @method getFields
     * @return array An array of fields and their constraints.
     */
    public function getFields(): array {
        return $this->fields;
    }

    /**
     * Pick only a subset of fields from the schema.
     * @method only
     * @param array $fieldNames An array of field names to pick.
     * @return Schema A new instance of the Schema class containing the picked fields.
     */
    public function only(array $fieldNames): Schema {
        $schema = new Schema();
        foreach ($fieldNames as $fieldName) {
            if (isset($this->fields[$fieldName])) {
                $schema->fields[$fieldName] = $this->fields[$fieldName];
            }
        }
        return $schema;
    }
}

/**
 * Field class represents a field in a schema with its validation constraints.
 */
class Field {
    protected string $name;
    protected array $constraints = [];

    /**
     * Constructor for Field class.
     * @method __construct
     * @param string $name The name of the field.
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * Add a constraint with a custom error message for the field.
     * @method addConstraint
     * @param string $constraint The type of constraint.
     * @param mixed $value The value of the constraint.
     * @param string $message The custom error message for the constraint.
     * @return Field The current instance of the Field class.
     */
    public function addConstraint($constraint, $value, $message): self {
        $this->constraints[$constraint] = [
            'value' => $value,
            'message' => $message,
        ];
        return $this;
    }

    /**
     * Get the name of the field.
     * @method getName
     * @return string The name of the field.
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get all constraints defined for the field.
     * @method getConstraints
     * @return array An array of constraints for the field.
     */
    public function getConstraints(): array {
        return $this->constraints;
    }
}

/**
 * Validator class validates data against a given schema.
 */
class Validator {
    
    /**
     * Validate data against the given schema.
     * @method validate
     * @param array $data The data to validate.
     * @param Schema $schema The schema containing validation rules.
     * @throws ValidationError If validation fails.
     */
    public function validate($data, Schema $schema): void {
        $errors = [];
        foreach ($schema->getFields() as $fieldName => $field) {
            // Apply htmlspecialchars, stripslashes, and trim to the field value if it exists -----------------
            $value = isset($data[$fieldName]) ? trim(stripslashes(htmlspecialchars($data[$fieldName]))) : null;
            if (!isset($value)) {
                foreach ($field->getConstraints() as $constraint => $details) {
                    if ($constraint === 'required' && $details['value'] === true) {
                        $errors[$fieldName][] = $details['message'] ?? "Field '$fieldName' is required.";
                    }
                }
            } else {
                foreach ($field->getConstraints() as $constraint => $details) {
                    if($constraint === 'required' && trim($value) === '') {
                        $errors[$fieldName][] = $details['message'] ?? "Field '$fieldName' is required.";
                    }
                    if($constraint === 'length' && strlen($value) != $details['value']) {
                        $errors[$fieldName][] = $details['message'] ?? "Field '$fieldName' must have exactly {$details['value']} characters.";
                    }
                    if ($constraint === 'minlength' && strlen($value) < $details['value']) {
                        $errors[$fieldName][] = $details['message'] ?? "Field '$fieldName' must have at least {$details['value']} characters.";
                    }
                    if ($constraint === 'maxlength' && strlen($value) > $details['value']) {
                        $errors[$fieldName][] = $details['message'] ?? "Field '$fieldName' must have at most {$details['value']} characters.";
                    }
                    if ($constraint === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$fieldName][] = $details['message'] ?? "Field '$fieldName' must be a valid email address.";
                    }
                    if ($constraint === 'pattern' && !preg_match($details['value'], $value)) {
                        $errors[$fieldName][] = $details['message'] ?? "Field '$fieldName' must match the pattern '{$details['value']}'.";
                    }
                    if ($constraint === 'enums' && !in_array($value, $details['value'])) {
                        $errors[$fieldName][] = $details['message'] ?? "Field '$fieldName' must be one of " . implode(', ', $details['value']);
                    }
                    if($constraint === 'equals' && $value !== $details['value']) {
                        $errors[$fieldName][] = $details['message'] ?? "Field '$fieldName' must be equal to '{$details['value']}'.";
                    }
                }
            }
        }
        if (!empty($errors)) {
            error_log("Validation errors: " . json_encode($errors)); // Log error
            throw new ValidationError($errors);
        }
    }
}

/**
 * ValidationError class represents an error that occurs during validation.
 */
class ValidationError extends Exception {
    public array $errors;

    /**
     * Constructor for ValidationError class.
     * @method __construct
     * @param array $errors An array containing validation errors.
     */
    public function __construct(array $errors) {
        parent::__construct('Validation_Error');
        $this->errors = $errors;
    }
}



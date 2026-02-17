<?php
declare(strict_types=1);

/**
 * Validate that required fields are present and non-empty.
 *
 * @param  array<string, mixed>  $data    Input data (e.g. $_POST)
 * @param  string[]              $fields  Field names that must be non-empty
 * @return array<string, string>          Errors keyed by field name (empty = valid)
 */
function validateRequired(array $data, array $fields): array
{
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    return $errors;
}

/**
 * Validate a single field against a list of rules.
 *
 * Supported rules:
 *   'required'   — must not be empty
 *   'email'      — must be a valid email address
 *   'password'   — must pass isValidPassword()
 *   'min:N'      — must have at least N characters
 *   'max:N'      — must have at most N characters
 *   'numeric'    — must be a numeric value
 *
 * Returns the first error string, or null if all rules pass.
 */
function validateField(string $field, mixed $value, array $rules): ?string
{
    $label = ucfirst(str_replace('_', ' ', $field));

    foreach ($rules as $rule) {
        if ($rule === 'required' && (is_null($value) || trim((string)$value) === '')) {
            return $label . ' is required';
        }

        // Skip remaining rules if field is empty and not required
        if (is_null($value) || trim((string)$value) === '') {
            continue;
        }

        if ($rule === 'email' && !isValidEmail((string)$value)) {
            return 'Please enter a valid email address';
        }

        if ($rule === 'password' && !isValidPassword((string)$value)) {
            return 'Password must be at least 8 characters with one uppercase letter and one number';
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int)substr($rule, 4);
            if (strlen((string)$value) < $min) {
                return $label . ' must be at least ' . $min . ' characters';
            }
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int)substr($rule, 4);
            if (strlen((string)$value) > $max) {
                return $label . ' must not exceed ' . $max . ' characters';
            }
        }

        if ($rule === 'numeric' && !is_numeric($value)) {
            return $label . ' must be a number';
        }
    }

    return null;
}

/**
 * Validate multiple fields at once using a rules map.
 *
 * Example:
 *   $errors = validateFields($_POST, [
 *       'email'    => ['required', 'email'],
 *       'password' => ['required', 'password'],
 *   ]);
 *
 * @param  array<string, mixed>          $data
 * @param  array<string, string[]>       $rules
 * @return array<string, string>         Errors keyed by field name
 */
function validateFields(array $data, array $rules): array
{
    $errors = [];
    foreach ($rules as $field => $fieldRules) {
        $error = validateField($field, $data[$field] ?? null, $fieldRules);
        if ($error !== null) {
            $errors[$field] = $error;
        }
    }
    return $errors;
}

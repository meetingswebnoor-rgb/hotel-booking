<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Server-side validation. Usage:
 *   $v = Validator::make($request->all(), ['email' => 'required|email', 'name' => 'required|max:100']);
 *   if ($v->fails()) { ... $v->errors() ... }
 */
final class Validator
{
    private array $errors = [];

    private function __construct(private readonly array $data, private readonly array $rules)
    {
        $this->run();
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;

            foreach (explode('|', $ruleString) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, (string) $name, $param);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void
    {
        $isEmpty = $value === null || $value === '';

        switch ($rule) {
            case 'required':
                if ($isEmpty) {
                    $this->addError($field, "The {$field} field is required.");
                }
                break;

            case 'email':
                if (!$isEmpty && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $this->addError($field, "The {$field} field must be a valid email address.");
                }
                break;

            case 'numeric':
                if (!$isEmpty && !is_numeric($value)) {
                    $this->addError($field, "The {$field} field must be numeric.");
                }
                break;

            case 'min':
                if (!$isEmpty && mb_strlen((string) $value) < (int) $param) {
                    $this->addError($field, "The {$field} field must be at least {$param} characters.");
                }
                break;

            case 'max':
                if (!$isEmpty && mb_strlen((string) $value) > (int) $param) {
                    $this->addError($field, "The {$field} field must not exceed {$param} characters.");
                }
                break;

            case 'in':
                $options = $param !== null ? explode(',', $param) : [];
                if (!$isEmpty && !in_array((string) $value, $options, true)) {
                    $this->addError($field, "The {$field} field must be one of: {$param}.");
                }
                break;

            case 'unique':
                if (!$isEmpty && $param !== null) {
                    [$table, $column] = array_pad(explode(',', $param, 2), 2, $field);
                    if (Database::first((string) $table, [(string) $column => $value]) !== null) {
                        $this->addError($field, "The {$field} field must be unique.");
                    }
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}

<?php namespace App\Core\Helpers;

final class Result {
    public function __construct(
        public bool $ok,
        public mixed $val = null,
        public ?string $error = null
    ) {}

    /**
     * @param array<int,mixed> $arguments
     */
    public function __call(string $name, array $arguments = []): self {
        if (!$this->ok) {
            return $this;
        }
        return self::OK($this->val->$name(...$arguments));
    }

    public function __get(string $name): mixed {
        if (!$this->ok) {
            return $this;
        }
        return self::OK($this->val->$name);
    }

    public static function OK(mixed $val = null): self {
        return new self(ok: true, val: $val);
    }

    public static function ERROR(string $error_msg): self {
        return new self(ok: false, error: $error_msg);
    }

    public static function TODO(string $msg): self {
        return new self(ok: false, error: "[NOT IMPLEMENTED]: {$msg}");
    }

    public function prefix(string $prefix): self {
        $this->error = "{$prefix}: {$this->error}";
        return $this;
    }

    public function log(?string $prefix = null): self {
        $prefix = isset($prefix) ? $prefix.': ' : '';
        if (!isset($this->error)) {
            Log::info($prefix . "No error");
        }
        Log::error($prefix . strval($this->error));
        return $this;
    }
}

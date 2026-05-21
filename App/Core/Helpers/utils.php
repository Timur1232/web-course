<?php namespace App\Core\Helpers;
use App\Core\Context\Router;
use App\Core\View\View;

final class Helpers {
    private function __construct() {}

    public static function var_dump_str(mixed $val): string {
        ob_start();
        var_dump($val);
        return ob_get_clean();
    }

    public static function var_dump_preln(mixed $val): void {
        echo '<pre>';
        var_dump($val);
        echo '</pre><br>';
    }

    /**
     * @param array<int,mixed> $arrays
     * @return array<int,mixed>
     */
    public static function zip(array ...$arrays): array {
        return array_map(null, ...$arrays);
    }
}

final class Error {
    private function __construct() {}

    // NOTE: Will hijack normal behaviour of Router class and kill the server,
    // because it is most exceptional situation.
    public static function assert(bool $cond, string $msg): void {
        if (!$cond) {
            Log::assert($msg);
            http_response_code(500);
            header('HX-Retarget: body');
            header('HX-Reswap: innerHTML');
            $err_comp = Router::$internal_error ?? View::error_component('500 Internal Error', 'Oops...');
            echo $err_comp->render();
            die();
        }
    }
}

final class Log {
    private function __construct() {}

    public static mixed $stdin = STDIN;
    public static mixed $stdout = STDOUT;
    public static mixed $stderr = STDERR;

    public static function printf(string $fmt, mixed ...$args): void {
        fprintf(self::$stdout, $fmt, ...$args);
    }

    public static function printfln(string $fmt, mixed ...$args): void {
        fprintf(self::$stdout, "$fmt\n", ...$args);
    }

    public static function println_green(string $msg): void {
        fputs(self::$stderr, "\e[32m{$msg}\e[0m\n");
    }

    public static function println_err(string $msg): void {
        fputs(self::$stderr, "\e[91m{$msg}\e[0m\n");
    }

    public static function trace(string $msg, ?string $file = null, ?int $line = null): void {
        $line ??= 0;
        $fl = isset($file) ? "{$file}:{$line}: " : '';
        fprintf(self::$stdout, "\e[37m[TRACE] {$fl}{$msg}\e[0m\n");
    }

    public static function info(string $msg, ?string $file = null, ?int $line = null): void {
        $line ??= 0;
        $fl = isset($file) ? "{$file}:{$line}: " : '';
        fprintf(self::$stdout, "\e[32m[INFO] {$fl}{$msg}\e[0m\n");
    }

    public static function warning(string $msg, ?string $file = null, ?int $line = null): void {
        $line ??= 0;
        $fl = isset($file) ? "{$file}:{$line}: " : '';
        fprintf(self::$stdout, "\e[33m[WARNING] {$fl}{$msg}\e[0m\n");
    }

    public static function error(string $msg, ?string $file = null, ?int $line = null): void {
        $line ??= 0;
        $fl = isset($file) ? "{$file}:{$line}: " : '';
        fprintf(self::$stderr, "\e[91m[ERROR] {$fl}{$msg}\e[0m\n");
    }

    public static function assert(string $msg, ?string $file = null, ?int $line = null): void {
        $line ??= 0;
        $fl = isset($file) ? "{$file}:{$line}: " : '';
        fprintf(self::$stderr, "\e[31m[ASSERT] {$fl}{$msg}\e[0m\n");
    }

    public static function log(string $msg, ?string $file = null, ?int $line = null): void {
        $line ??= 0;
        $fl = isset($file) ? "{$file}:{$line}: " : '';
        fprintf(self::$stdout, "[LOG] {$fl}{$msg}\n");
    }
}

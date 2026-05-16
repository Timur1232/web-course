<?php namespace App\Core\Helpers;
use App\Core\View\View;
use App\Views\CommonView;

final class Helpers {
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
    public static function send_error_msg_and_die(int $code, string $msg): void {
        while (ob_end_clean());
        http_response_code($code);
        echo $msg;
        die();
    }

    public static function not_found(string $path): void {
        $err = CommonView::layout(View::template('error', data: [
            'title' => '404 Not Found',
            'msg'   => "{$path} не найден.",
        ]), title: 'Упс', page_name: 'error');
        ob_start();
        $err->render();
        $msg = ob_get_clean();
        header('HX-Retarget: body');
        header('HX-Reswap: innerHTML');
        self::send_error_msg_and_die(404, $msg);
    }

    public static function method_not_allowed(): void {
        $err = CommonView::layout(View::template('error', data: [
            'title' => '405 Method Not Allowed',
        ]), title: 'Упс', page_name: 'error');
        ob_start();
        $err->render();
        $msg = ob_get_clean();
        header('HX-Retarget: body');
        header('HX-Reswap: innerHTML');
        self::send_error_msg_and_die(405, $msg);
    }

    public static function internal_error(): void {
        $err = CommonView::layout(View::template('error', data: [
            'title' => '500 Internal Server Error',
        ]), title: 'Упс', page_name: 'error');
        ob_start();
        $err->render();
        $msg = ob_get_clean();
        header('HX-Retarget: body');
        header('HX-Reswap: innerHTML');
        self::send_error_msg_and_die(500, $msg);
    }

    public static function assert(bool $cond, string $msg): void {
        if (!$cond) {
            Log::assert($msg);
            self::internal_error();
        }
    }
}

final class Log {
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

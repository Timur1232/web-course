<?php namespace App\Core\Test;
use App\Core\Helpers\Result;
use App\Core\Helpers\Defer;
use App\Core\Helpers\Log;
use ReflectionClass;
use Attribute;
use Exception;
use Throwable;

#[Attribute(Attribute::TARGET_METHOD)]
final class Test {
    public function __construct(
        public string $test_info  = 'no info',
        public bool $should_throw = false,
    ) {}

    public static function assert(bool $cond, ?string $msg = null): void {
        if (!$cond) {
            $msg = self::format_msg($msg);
            throw new Exception("assert: \$cond == false.{$msg}");
        }
    }

    /**
     * @param Result<mixed> $res
     */
    public static function expect_ok(Result $res, ?string $msg = null): void {
        if (!$res->ok) {
            $msg = self::format_msg($msg);
            throw new Exception("expect_ok: Result contain error: {$res->error}.{$msg}");
        }
    }

    /**
     * @param Result<mixed> $res
     */
    public static function expect_error(Result $res, ?string $msg = null): void {
        if ($res->ok) {
            $msg = self::format_msg($msg);
            $val_str = isset($res->val) ? "\nValue:\n".print_r($res->val) : '';
            throw new Exception("expect_error: Result is ok.{$val_str}{$msg}");
        }
    }

    public static function crash(?string $msg = null): void {
        $msg = self::format_msg($msg);
        throw new Exception("crash: Programm killed.{$msg}");
    }

    public static function is_array(mixed $obj, ?string $msg = null): void {
        if (!is_array($obj)) {
            $msg = self::format_msg($msg);
            $type = gettype($obj);
            throw new Exception("is_array: Object is of type {$type} and not array.{$msg}");
        }
    }

    /**
     * @param array<mixed,mixed> $arr1
     * @param array<mixed,mixed> $arr2
     *
     * Matches arrays both on keys and values
     */
    public static function match_arrays_kv(array $arr1, array $arr2, ?string $msg = null): void {
        self::is_array($arr1, $msg);
        self::is_array($arr2, $msg);
        $msg = self::format_msg($msg);
        $arr1_len = count($arr1);
        $arr2_len = count($arr2);
        $arr1_str = print_r($arr1, true);
        $arr2_str = print_r($arr2, true);
        if ($arr1_len !== $arr2_len) {
            throw new Exception("match_arrays: Sizes of arrays dont match: count(\$arr1) == {$arr1_len}, count(\$arr2) == {$arr2_len}.{$msg}\nArray1: {$arr1_str}\nArray2: {$arr2_str}");
        }

        $diff = [];
        foreach ($arr1 as $k1 => $v1) {
            if (!isset($arr2[$k1]) || $arr2[$k1] !== $v1) {
                $diff[$k1] = $v1;
            }
        }

        if (count($diff) !== 0) {
            $diff_str = print_r($diff, true);
            throw new Exception(<<<STR
                match_arrays: Arrays keys and values dont match.{$msg}
                Array1: {$arr1_str}
                Array2: {$arr2_str}
                Diff (array 1): {$diff_str}
                STR);
        }
    }

    /**
     * @param array<mixed,mixed> $arr1
     * @param array<mixed,mixed> $arr2
     *
     * Matches arrays only on values
     */
    public static function match_arrays_values(array $arr1, array $arr2, ?string $msg = null): void {
        $msg = self::format_msg($msg);
        $arr1_len = count($arr1);
        $arr2_len = count($arr2);
        if ($arr1_len !== $arr2_len) {
            throw new Exception("match_arrays_values: Sizes of arrays dont match: count(\$arr1) == {$arr1_len}, count(\$arr2) == {$arr2_len}.{$msg}");
        }
        $diff = [];
        foreach ($arr1 as $v) {
            if (!in_array($v, $arr1)) {
                $diff[] = $v;
            }
        }
        $arr1_str = print_r($arr1, true);
        $arr2_str = print_r($arr2, true);
        $diff_str = print_r($diff, true);
        if (count($diff) !== 0) {
            throw new Exception(<<<STR
                match_arrays_values: Arrays values dont match.{$msg}
                Array1: {$arr1_str}
                Array2: {$arr2_str}
                Diff:   {$diff_str}
                STR);
        }
    }

    public static function match_file_contents(string $file_path, string $compare_str, ?string $msg = null): void {
        $msg = self::format_msg($msg);
        if (!file_exists($file_path)) {
            throw new Exception("match_file_contents: File {$file_path} not exists.{$msg}");
        }
        $file_contents = file_get_contents($file_path);
        if ($file_contents === false) {
            throw new Exception("match_files: Unable to read {$file_path} contents.{$msg}");
        }
        if ($file_contents !== $compare_str) {
            throw new Exception(<<<STR
                match_file_contents: Contents of {$file_path} not matching with given string.{$msg}
                {$file_path} contents:
                {$file_contents}
                STR);
        }
    }

    public static function match_files(string $file_path1, string $file_path2, ?string $msg = null): void {
        $msg = self::format_msg($msg);
        if (!file_exists($file_path1)) {
            throw new Exception("match_files: File {$file_path1} not exists.{$msg}");
        } else if (!file_exists($file_path2)) {
            throw new Exception("match_files: File {$file_path2} not exists.{$msg}");
        }

        $contents1 = file_get_contents($file_path1);
        if ($contents1 === false) {
            throw new Exception("match_files: Unable to read {$file_path1} contents.{$msg}");
        }
        $contents2 = file_get_contents($file_path2);
        if ($contents2 === false) {
            throw new Exception("match_files: Unable to read {$file_path2} contents.{$msg}");
        }

        if ($contents1 !== $contents2) {
            throw new Exception(<<<STR
                match_files: Contents of {$file_path1} and {$file_path1} dont matching.{$msg}
                {$file_path1} contents:
                {$contents1}
                {$file_path2} contents:
                {$contents2}
                STR);
        }
    }

    private static function format_msg(?string $msg): string {
        return isset($msg) ? "\nMessage: {$msg}" : '';
    }
}

final class TestDriver {

    /**
     * @var string[] $test_classes
     */
    public static array $test_classes = [];

    public static $stdin;
    public static $stdout;
    public static $stderr;

    /**
     * @param string[] $test_classes
     */
    public static function setup(array $test_classes = []): void {
        require_once './App/Core/Init.php';
        if (!defined('TEST_STDIN'))  define('TEST_STDIN',  fopen('php://stdin', 'rb'));
        if (!defined('TEST_STDOUT')) define('TEST_STDOUT', fopen('php://stdout', 'wb'));
        if (!defined('TEST_STDERR')) define('TEST_STDERR', fopen('php://stderr', 'wb'));
        self::$stdin  = TEST_STDIN;
        self::$stdout = TEST_STDOUT;
        self::$stderr = TEST_STDERR;
        self::$test_classes = $test_classes;
    }

    public static function print(string $msg = ''): void {
        fputs(TEST_STDOUT, "{$msg}");
    }

    public static function println(string $msg = ''): void {
        self::print($msg."\n");
    }

    public static function print_green(string $msg = ''): void {
        fputs(TEST_STDOUT, "\e[32m{$msg}\e[0m");
    }

    public static function print_err(string $msg = ''): void {
        fputs(TEST_STDOUT, "\e[91m{$msg}\e[0m");
    }

    public static function println_green(string $msg = ''): void {
        self::print_green($msg."\n");
    }

    public static function println_red(string $msg = ''): void {
        self::print_err($msg."\n");
    }

    public static function print_yellow(string $msg = ''): void {
        fputs(TEST_STDOUT, "\e[33m{$msg}\e[0m");
    }

    public static function println_yellow(string $msg = ''): void {
        self::print_yellow("{$msg}\n");
    }

    /**
     * @param ?array<string> $cases
     */
    public static function run_tests(?array $cases = null): void {
        self::println();
        $failed = [];
        $succeded = [];
        $skipped = [];
        $tests_count = 0;

        $dev_null_handler = fopen('/dev/null', 'wb');
        Defer::d($_, fclose(...), $dev_null_handler);

        foreach (self::$test_classes as $class_name) {
            if (!isset($cases) || in_array($class_name, $cases)) {
                $methods = self::get_test_methods($class_name);
                if (count($methods) === 0) continue;
                self::println("RUNING TESTS FOR: {$class_name}");
                $i = 1;
                foreach ($methods as [$m, $a]) {
                    self::print("  {$i}) '{$a->test_info}' - {$class_name}::{$m->getName()}: ");

                    Log::$stdin  = $dev_null_handler;
                    Log::$stdout = $dev_null_handler;
                    Log::$stderr = $dev_null_handler;

                    if (!$m->isStatic()) {
                        self::println_yellow("Non static methods not supported: {$class_name}::{$m->getName()}. Skipping.");
                        $skipped[] = ["{$class_name}::{$m->getName()}", $a];
                        continue;
                    }
                    if ($m->isPrivate() || $m->isProtected()) $m->setAccessible(true);

                    $tests_count++;
                    try {
                        $m->invoke(null);
                        if ($a->should_throw) {
                            self::println_red("SHOULD THROW: ERROR");
                            $failed[] = ["{$class_name}::{$m->getName()}", $a];
                        } else {
                            $succeded[] = ["{$class_name}::{$m->getName()}", $a];
                            self::println_green("OK");
                        }
                    } catch (Throwable $e) {
                        if ($a->should_throw) {
                            self::println_green("SHOULD THROW: OK");
                            $succeded[] = ["{$class_name}::{$m->getName()}", $a];
                        } else {
                            self::println_red("ERROR");
                            self::println_red($e->getMessage());
                            $failed[] = ["{$class_name}::{$m->getName()}", $a];
                        }
                    }
                    $i++;
                }
            }
        }
        self::println("\nTests amount:   {$tests_count}");
        $success_count = count($succeded);
        self::println_green("Tests succeded: {$success_count}");
        $skip_count = count($skipped);
        if ($skip_count !== 0) {
            self::println_yellow("Tests skipped:  {$skip_count}");
            foreach ($skipped as $skip) {
                $throw = '';
                if ($skip[1]->should_throw) $throw = 'Should throw: ';
                self::println("  - {$throw}{$skip[0]}: '{$skip[1]->test_info}'");
            }
        }
        $fail_count = count($failed);
        if ($fail_count !== 0) {
            self::println_red("Tests failed:   {$fail_count}");
            foreach ($failed as $fail) {
                $throw = '';
                if ($fail[1]->should_throw) $throw = 'Should throw: ';
                self::println("  - {$throw}{$fail[0]}: '{$fail[1]->test_info}'");
            }
        }
        else self::println_green("All tests succeded");
    }

    /**
     * @template T
     * @param class-string<T> $class_name
     * @return array<int,array{0: ReflectionMethod, 1: Test}>
     */
    private static function get_test_methods(string $class_name): array {
        try {
            $r = new ReflectionClass($class_name);
        } catch (Throwable $e) {
            self::println_red("REFLECTION ERROR: {$class_name}");
            self::println_red($e->getMessage());
            return [];
        }
        $ret = [];
        foreach ($r->getMethods() as $m) {
            foreach ($m->getAttributes() as $attr) {
                if ($attr->getName() === Test::class) {
                    $ret[] = [$m, $attr->newInstance()];
                    break;
                }
            }
        }
        return $ret;
    }
}

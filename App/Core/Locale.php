<?php namespace App\Core;
use App\Core\Helpers\Error;

final class Locale {
    private static string $lang = 'ru';
    private static array $loaded = [];

    public static function set_language(string $lang): void {
        if (!in_array($lang, ['ru', 'en'])) {
            $lang = 'ru';
        }
        self::$lang = $lang;
    }

    public static function get_language(): string {
        return self::$lang;
    }

    public static function get(string $key, array $replace = []): string {
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2) {
            Error::assert(false, __METHOD__.": Invalid key format '{$key}'. Use 'file.key'.");
        }
        [$file, $inner_key] = $parts;

        if (!isset(self::$loaded[self::$lang][$file])) {
            $path = "./App/Locale/" . self::$lang . "/{$file}.php";
            if (!file_exists($path)) {
                Error::assert(false, "Locale::get: Translation file '{$path}' not found.");
            }
            self::$loaded[self::$lang][$file] = require $path;
        }

        $dictionary = self::$loaded[self::$lang][$file];
        $value = $dictionary[$inner_key] ?? $key;

        if (!empty($replace)) {
            foreach ($replace as $placeholder => $replacement) {
                $value = str_replace("{{{$placeholder}}}", $replacement, $value);
            }
        }

        return $value;
    }

    private function __construct() {}
}

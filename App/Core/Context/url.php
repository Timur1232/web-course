<?php namespace App\Core\Context;
use App\Core\Helpers\Helpers;
use App\Core\Test\Test;

final class URL {
    /** @param array<string, string> $query */
    public function __construct(
        public string $path,
        public array $query,
    ) { }

    public static function from(string $url): self {
        $parsed = parse_url($url);
        return new self(
            isset($parsed['path']) ? $parsed['path'] : '/',
            self::parse_query(isset($parsed['query']) ? $parsed['query'] : ''),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function split_path(string $path): array {
        return $path === '/' ? [] : array_slice(explode('/', $path), 1);
    }

    /**
     * @return array<string, string>
     */
    public static function parse_query(string $query_str): array {
        if (!$query_str) return [];
        $kvs = explode('&', $query_str);
        $query = [];
        foreach ($kvs as $kv) {
            [0 => $key, 1 => $value] = explode('=', $kv);
            $query[$key] = $value;
        }
        return $query;
    }

    public function match(string $template_path): bool {
        $splited_template = self::split_path($template_path);
        $splited_path = self::split_path($this->path);
        if (count($splited_path) !== count($splited_template)) return false;

        foreach (Helpers::zip($splited_path, $splited_template) as [$real, $templ]) {
            if (isset($templ[0]) && $templ[0] === ':') continue;
            if ($templ !== $real) return false;
        }

        return true;
    }

    /*
     * WARNING: There are no validation in this method. To validate template use `match` method
     *
     * @return array<string,string>
     */
    public function bind_values(string $template_path): array {
        $ret = [];
        foreach (Helpers::zip(explode('/', $this->path), explode('/', $template_path)) as [$real, $templ]) {
            if (isset($templ[0]) && $templ[0] === ':') {
                $ret[substr($templ, 1)] = $real;
            }
        }
        return $ret;
    }

    #[Test('url matches')]
    private static function matches_test(): void {
        $s = self::from('/test/foo/bar/baz');

        Test::assert($s->match('/test/foo/bar/baz'), 'simple match');
        Test::assert(!$s->match('/test/baz/bar/foo'), 'simple not match');

        Test::assert($s->match('/test/:id/bar/:action'), 'match with bindings');
        Test::assert(!$s->match('/test/:id/foo/:action'), 'not match with bindings');

        $s = self::from('/');
        Test::assert($s->match('/'), 'root match');
        Test::assert(!$s->match('/urmom'), 'root not match');
        Test::assert(!$s->match('/:id'), 'root not match with bindings');
    }

    #[Test('url bindings')]
    private static function binding_test(): void {
        $s = self::from('/urmom');
        $res = $s->bind_values('/:obj');
        Test::match_arrays_kv($res, [
            'obj' => 'urmom'
        ], 'simple bind');

        $s = self::from('/test/delete/69');

        $res = $s->bind_values('/test/:action/:id');
        Test::match_arrays_kv($res, [
            'action' => 'delete',
            'id'     => '69'
        ], 'binds one after one');

        $s = self::from('/test/delete/bar/69');

        $res = $s->bind_values('/test/:action/bar/:id');
        Test::match_arrays_kv($res, [
            'action' => 'delete',
            'id'     => '69'
        ], 'binds with thing in between');
    }

    #[Test('query parse')]
    private static function query_parse_test(): void {
        $parsed = parse_url('/test?action=urmom&id=69');
        Test::assert(isset($parsed['query']), 'should never fail');

        $res = self::parse_query($parsed['query']);
        Test::match_arrays_kv($res, [
            'action' => 'urmom',
            'id'     => '69'
        ], 'simple match');

        $res = self::parse_query('');
        Test::assert(count($res) === 0, 'no query');
    }
}

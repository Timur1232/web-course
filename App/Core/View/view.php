<?php namespace App\Core\View;
use Closure;

enum Js_Script_Type : string {
    case Text   = 'text/javascript';
    case Module = 'module';
}

final class Js_Script {
    public function __construct(
        public string $src = '',
        public Js_Script_Type $type = Js_Script_Type::Text,
        public bool $defer = false,
    ) { }

    public static function from(string $src, Js_Script_Type $type = Js_Script_Type::Text, bool $defer = false): self {
        return new self($src, $type, $defer);
    }

    public function render_script(): string {
        $defer = $this->defer ? 'defer' : '';
        return "<script src=\"{$this->src}\" type=\"{$this->type->value}\" {$defer}></script>";
    }
}

final class View {
    private function __construct() {}

    public const DEFAULT_TITLE = 'Мой сайт';

    /*
    * @param array<string, mixed> $data
    */
    public static function template(string $template_page, array $data = []): Template_Component {
        return new Template_Component($template_page, $data);
    }

    public static function empty(): Component_Func {
        return self::func(fn() => '');
    }
    /**
     * @param Closure(): string $callback
     */
    public static function func(Closure $callback): Component_Func {
        return new Component_Func($callback);
    }

    public static function string(string $str): Component_Func {
        return self::func(fn() => $str);
    }

    public static function msg_tag(string $msg, string $id = 'msg'): Component_Func {
        return self::func(function () use ($msg, $id) {
            return <<<HTML
            <span id="{$id}">{$msg}</span>
            HTML;
        });
    }

    public static function error_tag(string $err_msg, string $id = 'error'): Component_Func {
        return self::msg_tag($err_msg, $id);
    }
}

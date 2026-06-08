<?php namespace App\Views;

use App\Core\Locale;
use App\Core\View\Component_Func;
use App\Core\View\View;

final class Categories_View {
    private function __construct() {}

    public static function render_form(string $mode, ?string $error = null, ?int $id = null, string $name_ru = '', string $name_en = ''): Component_Func {
        $action = $mode === 'add' ? '/products/add_category' : "/products/edit_category/{$id}";
        $title = Locale::get("category.{$mode}_title");
        $error_html = $error ? '<div class="form-error">' . $error . '</div>' : '';

        return View::func(function () use ($action, $title, $error_html, $name_ru, $name_en) {
            $__ = fn($key) => Locale::get("category.{$key}");
            return <<<HTML
            <div class="form">
                <form method="post" action="{$action}">
                    <h2 class="form-title">{$title}</h2>
                    {$error_html}
                    <label>{$__('edit_name_ru')}</label>
                    <input type="text" name="name_ru" value="{$name_ru}" required>
                    <label>{$__('edit_name_en')}</label>
                    <input type="text" name="name_en" value="{$name_en}" required>
                    <button type="submit" class="form-submit">{$__('submit')}</button>
                </form>
            </div>
            HTML;
        });
    }
}

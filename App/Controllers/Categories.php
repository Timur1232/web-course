<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Locale;
use App\Core\Model\DB_Model;
use App\Core\Model\DB_Type;
use App\Core\View\Component_Func;
use App\Core\View\View;
use App\Views\Common_View;

final class Categories {
    private function __construct() {}

    public static function add_form(Request $req): Response {
        $user = $req->additional['user'];
        if (is_null($user)) return Response::redirect('/');

        $content = self::render_form('add');
        return Response::view(Common_View::layout(
            $content,
            title: Locale::get('category.add_title'),
            page_name: 'products',
            user: $user
        ));
    }

    public static function create(Request $req): Response {
        $user = $req->additional['user'];
        if (!$user) return Response::redirect('/');

        $name_ru = trim($req->form['name_ru'] ?? '');
        $name_en = trim($req->form['name_en'] ?? '');
        if ($name_ru === '' || $name_en === '') {
            $content = self::render_form('add', 'Заполните все поля');
            return Response::view(Common_View::layout($content, title: Locale::get('category.add_title'), page_name: 'products', user: $user));
        }

        DB_Model::query("insert into categories (id) values (null)")->execute();
        $id = DB_Model::$conn->lastInsertId();

        DB_Model::query("insert into category_translations (category_id, lang_code, name) values (:id, 'ru', :name)")
            ->bind_values(['id' => $id, 'name' => $name_ru])->execute();
        DB_Model::query("insert into category_translations (category_id, lang_code, name) values (:id, 'en', :name)")
            ->bind_values(['id' => $id, 'name' => $name_en])->execute();

        return Response::redirect('/products');
    }

    public static function edit_form(Request $req): Response {
        $user = $req->additional['user'];
        if (!$user) return Response::redirect('/');
        $id = (int)$req->binds['id'];

        $translations = [];
        $rows = DB_Model::query("select lang_code, name from category_translations where category_id = :id")
            ->bind_values(['id' => $id])->fetch_all()->or_else([]);
        foreach ($rows as $r) {
            $translations[$r['lang_code']] = $r['name'];
        }
        $current_ru = $translations['ru'] ?? '';
        $current_en = $translations['en'] ?? '';

        $content = self::render_form('edit', null, $id, $current_ru, $current_en);
        return Response::view(Common_View::layout(
            $content,
            title: Locale::get('category.edit_title'),
            page_name: 'products',
            user: $user
        ));
    }

    public static function update(Request $req): Response {
        $user = $req->additional['user'];
        if (!$user) return Response::redirect('/');
        $id = (int)$req->binds['id'];

        $name_ru = trim($req->form['name_ru'] ?? '');
        $name_en = trim($req->form['name_en'] ?? '');
        if ($name_ru === '' || $name_en === '') {
            $content = self::render_form('edit', 'Заполните все поля', $id, $name_ru, $name_en);
            return Response::view(Common_View::layout($content, title: Locale::get('category.edit_title'), page_name: 'products', user: $user));
        }

        if (DB_Model::$current_db === DB_Type::SQLITE) {
            DB_Model::query('pragma foreign_keys = on')->execute();
        }
        DB_Model::query("delete from category_translations where category_id = :id")
            ->bind_values(['id' => $id])->execute();
        DB_Model::query("insert into category_translations (category_id, lang_code, name) values (:id, 'ru', :name)")
            ->bind_values(['id' => $id, 'name' => $name_ru])->execute();
        DB_Model::query("insert into category_translations (category_id, lang_code, name) values (:id, 'en', :name)")
            ->bind_values(['id' => $id, 'name' => $name_en])->execute();

        return Response::redirect('/products');
    }

    public static function delete(Request $req): Response {
        $user = $req->additional['user'];
        if (!$user) return Response::redirect('/');
        $id = (int)$req->binds['id'];

        if (DB_Model::$current_db === DB_Type::SQLITE) {
            DB_Model::query('pragma foreign_keys = on')->execute();
        }
        DB_Model::query("delete from categories where id = :id")
            ->bind_values(['id' => $id])->execute();

        return Response::redirect('/products');
    }

    private static function render_form(string $mode, ?string $error = null, ?int $id = null, string $name_ru = '', string $name_en = ''): Component_Func {
        $action = $mode === 'add' ? '/products/add_category' : "/products/edit_category/{$id}";
        $title = Locale::get("category.{$mode}_title");
        $error_html = $error ? '<div class="form-error">' . htmlspecialchars($error) . '</div>' : '';

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

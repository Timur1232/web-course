<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Locale;
use App\Core\Model\DB_Model;
use App\Core\View\Js_Script;
use App\Views\Common_View;
use App\Views\Promotions_View;
use App\Models\User_Privileges;

final class Promotions {
    private function __construct() {}

    public static function index(Request $req): Response {
        $lang = Locale::get_language();
        $user = $req->additional['user'] ?? null;
        $page = max(1, (int)($req->url->query['page'] ?? 1));
        $per_page = 10;

        $count_sql = "
            select count(*) as cnt
            from news n
            join news_translations nt on n.id = nt.news_id and nt.lang_code = :lang
            where n.type = 'promotion'
        ";
        $count_stmt = DB_Model::query($count_sql)?->bind_values(['lang' => $lang])?->execute();
        $total = (int)($count_stmt?->fetch()['cnt'] ?? 0);
        $total_pages = max(1, ceil($total / $per_page));

        $offset = ($page - 1) * $per_page;
        $sql = "
            select n.id, n.date, nt.title, nt.preview
            from news n
            join news_translations nt on n.id = nt.news_id and nt.lang_code = :lang
            where n.type = 'promotion'
            order by n.date desc
            limit :limit offset :offset
        ";
        $stmt = DB_Model::query($sql)?->bind_values([
            'lang' => $lang,
            'limit' => $per_page,
            'offset' => $offset,
        ])?->execute();

        $rows = $stmt?->fetch_all() ?: [];
        $items = array_map(fn($row) => (object)[
            'id' => $row['id'],
            'date' => $row['date'],
            'title' => $row['title'],
            'preview' => $row['preview'],
        ], $rows);

        $base_url = '/promotions';
        $comp = Promotions_View::list($items, $user, $page, $total_pages, $base_url);
        $title = Locale::get('promotions.list_title');
        return Response::view(Common_View::layout($comp, title: $title, page_name: 'promotions', user: $user));
    }

    public static function show(Request $req): Response {
        $lang = Locale::get_language();
        $id = (int)$req->binds['id'];
        $user = $req->additional['user'] ?? null;

        $stmt = DB_Model::query("
            select n.id, n.date, nt.title, nt.content
            from news n
            join news_translations nt on n.id = nt.news_id and nt.lang_code = :lang
            where n.id = :id and n.type = 'promotion'
        ")?->bind_values(['lang' => $lang, 'id' => $id])?->execute();

        $row = $stmt?->fetch();
        if (!$row) {
            return Response::redirect('/promotions');
        }

        $promotion = (object)[
            'id' => $row['id'],
            'date' => $row['date'],
            'title' => $row['title'],
            'content' => $row['content'],
        ];

        $comp = Promotions_View::detail($promotion, $user);
        $title = $promotion->title;
        return Response::view(Common_View::layout($comp, title: $title, page_name: 'promotions', user: $user));
    }

    public static function new(Request $req): Response {
        $user = self::require_admin($req);
        if (!$user) return Response::redirect('/');
        $comp = Promotions_View::form('/promotions/new');
        return Response::view(Common_View::layout($comp,
            title: Locale::get('promotions.create_title'),
            page_name: 'promotions',
            user: $user,
            scripts: self::editor_scripts()
        ));
    }

    public static function create(Request $req): Response
    {
        $user = self::require_admin($req);
        if (!$user) return Response::redirect('/');

        $date = $req->form['date'] ?? date('Y-m-d');
        $title_ru = trim($req->form['title_ru'] ?? '');
        $preview_ru = trim($req->form['preview_ru'] ?? '');
        $content_ru = $req->form['content_ru'] ?? '';
        $title_en = trim($req->form['title_en'] ?? '');
        $preview_en = trim($req->form['preview_en'] ?? '');
        $content_en = $req->form['content_en'] ?? '';

        if ($title_ru === '' || $title_en === '') {
            return Response::redirect('/promotions/new');
        }

        DB_Model::query("insert into news (date, type) values (:date, 'promotion')")
            ?->bind_values(['date' => $date])?->execute();
        $promo_id = DB_Model::$conn->lastInsertId();

        DB_Model::query("insert into news_translations (news_id, lang_code, title, preview, content) values (:id, 'ru', :title, :preview, :content)")
            ?->bind_values(['id' => $promo_id, 'title' => $title_ru, 'preview' => $preview_ru, 'content' => $content_ru])?->execute();
        DB_Model::query("insert into news_translations (news_id, lang_code, title, preview, content) values (:id, 'en', :title, :preview, :content)")
            ?->bind_values(['id' => $promo_id, 'title' => $title_en, 'preview' => $preview_en, 'content' => $content_en])?->execute();

        return Response::redirect('/promotions/' . $promo_id);
    }

    public static function edit(Request $req): Response {
        $user = self::require_admin($req);
        if (!$user) return Response::redirect('/');
        $id = (int)$req->binds['id'];

        $news_stmt = DB_Model::query("select id, date from news where id = :id and type = 'promotion'")
            ?->bind_values(['id' => $id])?->execute();
        $news_row = $news_stmt?->fetch();
        if (!$news_row) return Response::redirect('/promotions');

        $translations = [];
        $trans_stmt = DB_Model::query("select lang_code, title, preview, content from news_translations where news_id = :id")
            ?->bind_values(['id' => $id])?->execute();
        foreach ($trans_stmt?->fetch_all() ?: [] as $t) {
            $translations[$t['lang_code']] = (object)[
                'title' => $t['title'],
                'preview' => $t['preview'] ?? '',
                'content' => $t['content']
            ];
        }
        if (!isset($translations['ru'])) $translations['ru'] = (object)['title' => '', 'preview' => '', 'content' => ''];
        if (!isset($translations['en'])) $translations['en'] = (object)['title' => '', 'preview' => '', 'content' => ''];

        $promotion = (object)[
            'id' => $news_row['id'],
            'date' => $news_row['date'],
            'translations' => $translations,
        ];

        $comp = Promotions_View::form("/promotions/{$id}/edit", $promotion);
        return Response::view(Common_View::layout($comp,
            title: Locale::get('promotions.edit_title'),
            page_name: 'promotions',
            user: $user,
            scripts: self::editor_scripts()
        ));
    }

    public static function update(Request $req): Response {
        $user = self::require_admin($req);
        if (!$user) return Response::redirect('/');
        $id = (int)$req->binds['id'];

        $date = $req->form['date'] ?? date('Y-m-d');
        $title_ru = trim($req->form['title_ru'] ?? '');
        $preview_ru = trim($req->form['preview_ru'] ?? '');
        $content_ru = $req->form['content_ru'] ?? '';
        $title_en = trim($req->form['title_en'] ?? '');
        $preview_en = trim($req->form['preview_en'] ?? '');
        $content_en = $req->form['content_en'] ?? '';

        if ($title_ru === '' || $title_en === '') {
            return Response::redirect("/promotions/{$id}/edit");
        }

        DB_Model::query("update news set date = :date where id = :id and type = 'promotion'")
            ?->bind_values(['date' => $date, 'id' => $id])?->execute();

        $existing = DB_Model::query("select lang_code from news_translations where news_id = :id")
            ?->bind_values(['id' => $id])?->execute()?->fetch_all() ?: [];
        $existing_langs = array_column($existing, 'lang_code');

        $upsert = function(string $lang, string $title, string $preview, string $content) use ($id, $existing_langs) {
            if (in_array($lang, $existing_langs)) {
                DB_Model::query("update news_translations set title=:title, preview=:preview, content=:content where news_id=:id and lang_code=:lang")
                    ?->bind_values(compact('title', 'preview', 'content', 'id', 'lang'))?->execute();
            } else {
                DB_Model::query("insert into news_translations (news_id, lang_code, title, preview, content) values (:id, :lang, :title, :preview, :content)")
                    ?->bind_values(compact('id', 'lang', 'title', 'preview', 'content'))?->execute();
            }
        };
        $upsert('ru', $title_ru, $preview_ru, $content_ru);
        $upsert('en', $title_en, $preview_en, $content_en);

        return Response::redirect('/promotions/' . $id);
    }

    public static function delete(Request $req): Response {
        $user = self::require_admin($req);
        if (!$user) return Response::redirect('/');
        $id = (int)$req->binds['id'];
        if (\Config::IS_USING_SQLITE) {
            DB_Model::query('pragma foreign_keys = on')?->execute();
        }
        DB_Model::query("delete from news where id = :id and type = 'promotion'")
            ?->bind_values(['id' => $id])?->execute();
        return Response::redirect('/promotions');
    }

    private static function require_admin(Request $req): ?object {
        $user = $req->additional['user'] ?? null;
        if (!$user || $user->privilege !== User_Privileges::ADMIN) return null;
        return $user;
    }

    private static function editor_scripts(): array {
        return [
            Js_Script::from('//code.jquery.com/jquery-3.6.0.min.js'),
            Js_Script::from('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js'),
            Js_Script::from('https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js'),
        ];
    }
}

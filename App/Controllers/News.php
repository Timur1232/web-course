<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Locale;
use App\Core\Model\DB_Model;
use App\Core\Model\DB_Type;
use App\Core\View\Js_Script;
use App\Views\Common_View;
use App\Views\News_View;

final class News {
    private function __construct() {}

    public static function index(Request $req): Response {
        $lang = Locale::get_language();
        $user = $req->additional['user'] ?? null;
        $page = max(1, (int)($req->url->query['page'] ?? 1));
        $per_page = 5;

        $count_sql = "
            select count(*) as cnt
            from news n
            join news_translations nt on n.id = nt.news_id and nt.lang_code = :lang
            where n.type = 'news'
        ";
        $count_stmt = DB_Model::query($count_sql)->bind_values(['lang' => $lang])->fetch()->or_else([]);
        $total = (int)($count_stmt['cnt'] ?? 0);
        $total_pages = max(1, ceil($total / $per_page));

        $offset = ($page - 1) * $per_page;
        $sql = "
            select n.id, n.date, nt.title, nt.preview, nt.content
            from news n
            join news_translations nt on n.id = nt.news_id and nt.lang_code = :lang
            where n.type = 'news'
            order by n.date desc
            limit :limit offset :offset
        ";
        $rows = DB_Model::query($sql)->bind_values([
            'lang' => $lang,
            'limit' => $per_page,
            'offset' => $offset,
        ])->fetch_all()->or_else([]);

        $items = [];
        foreach ($rows as $row) {
            $preview = $row['preview'] ?? '';
            $items[] = (object)[
                'id' => $row['id'],
                'date' => $row['date'],
                'title' => $row['title'],
                'preview' => $preview,
            ];
        }

        $base_url = '/news';
        $comp = News_View::news_list($items, $user, $page, $total_pages, $base_url);
        $title = Locale::get('news.list_title');
        return Response::view(Common_View::layout($comp, title: $title, page_name: 'news', user: $user));
    }

    public static function show(Request $req): Response {
        $lang = Locale::get_language();
        $id = (int)$req->binds['id'];
        $user = $req->additional['user'] ?? null;

        $row = DB_Model::query("
            select n.id, n.date, nt.title, nt.preview, nt.content
            from news n
            join news_translations nt on n.id = nt.news_id and nt.lang_code = :lang
            where n.id = :id and n.type = 'news'
        ")->bind_values(['lang' => $lang, 'id' => $id])->fetch();
        if (!$row->ok) {
            return Response::redirect('/news');
        }
        $row = $row->val;

        $news = (object)[
            'id' => $row['id'],
            'date' => $row['date'],
            'title' => $row['title'],
            'content' => $row['content'],
        ];

        $comp = News_View::news_detail($news, $user);
        $title = $news->title;
        return Response::view(Common_View::layout($comp, title: $title, page_name: 'news', user: $user));
    }

    public static function new(Request $req): Response {
        $user = $req->additional['user'];
        $comp = News_View::news_form('/news/new');
        return Response::view(Common_View::layout($comp,
            title: Locale::get('news.create_title'),
            page_name: 'news',
            user: $user,
            scripts: [
                Js_Script::from('//code.jquery.com/jquery-3.6.0.min.js'),
                Js_Script::from('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js'),
                Js_Script::from('https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js'),
            ]
        ));
    }

    public static function create(Request $req): Response {
        $date = $req->form['date'] ?? date('Y-m-d');
        $title_ru = trim($req->form['title_ru'] ?? '');
        $content_ru = $req->form['content_ru'] ?? '';
        $title_en = trim($req->form['title_en'] ?? '');
        $content_en = $req->form['content_en'] ?? '';
        $preview_ru = trim($req->form['preview_ru'] ?? '');
        $preview_en = trim($req->form['preview_en'] ?? '');

        if ($title_ru === '' || $title_en === '') {
            return Response::redirect('/news/new');
        }

        DB_Model::begin_transaction();
        if (!DB_Model::query("insert into news (date, type) values (:date, 'news')")
            ->bind_values(['date' => $date])
            ->execute()->ok) {
            DB_Model::roll_back();
            return Response::redirect('/news/new');
        }
        $news_id = DB_Model::$conn->lastInsertId();
        $resp = Response::redirect('/news/' . $news_id);

        if (!DB_Model::query("insert into news_translations (news_id, lang_code, title, preview, content) values (:id, 'ru', :title, :preview, :content)")
            ->bind_values(['id' => $news_id, 'title' => $title_ru, 'preview' => $preview_ru, 'content' => $content_ru])
            ->execute()->ok) {
            DB_Model::roll_back();
            return $resp;
        }
        if (!DB_Model::query("insert into news_translations (news_id, lang_code, title, preview, content) values (:id, 'en', :title, :preview, :content)")
            ->bind_values(['id' => $news_id, 'title' => $title_en, 'preview' => $preview_en, 'content' => $content_en])
            ->execute()->ok) {
            DB_Model::roll_back();
            return $resp;
        }
        DB_Model::commit();

        return $resp;
    }

    public static function edit(Request $req): Response {
        $user = $req->additional['user'];
        $id = (int)$req->binds['id'];
        $news_row = DB_Model::query("select id, date from news where id = :id and type = 'news'")
            ->bind_values(['id' => $id])->fetch();
        if (!$news_row->ok) {
            return Response::redirect('/news');
        }
        $news_row = $news_row->val;

        $translations = [];
        $transl = DB_Model::query("select lang_code, title, preview, content from news_translations where news_id = :id")
            ->bind_values(['id' => $id])->fetch_all()->or_else([]);
        foreach ($transl as $t) {
            $translations[$t['lang_code']] = (object)[
                'title' => $t['title'],
                'preview' => $t['preview'],
                'content' => $t['content'],
            ];
        }

        if (!isset($translations['ru'])) $translations['ru'] = (object)['title' => '', 'content' => '', 'preview' => ''];
        if (!isset($translations['en'])) $translations['en'] = (object)['title' => '', 'content' => '', 'preview' => ''];

        $news = (object)[
            'id' => $news_row['id'],
            'date' => $news_row['date'],
            'translations' => $translations,
        ];

        $comp = News_View::news_form("/news/{$id}/edit", $news);
        return Response::view(Common_View::layout($comp,
            title: Locale::get('news.edit_title'),
            page_name: 'news',
            user: $user,
            scripts: [
                Js_Script::from('//code.jquery.com/jquery-3.6.0.min.js'),
                Js_Script::from('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js'),
                Js_Script::from('https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js'),
            ]
        ));
    }

    public static function update(Request $req): Response {
        $id = (int)$req->binds['id'];
        $date = $req->form['date'] ?? date('Y-m-d');
        $title_ru = trim($req->form['title_ru'] ?? '');
        $content_ru = $req->form['content_ru'] ?? '';
        $title_en = trim($req->form['title_en'] ?? '');
        $content_en = $req->form['content_en'] ?? '';

        $err_resp = Response::redirect("/news/{$id}/edit");

        if ($title_ru === '' || $title_en === '') {
            return $err_resp;
        }

        DB_Model::begin_transaction();
        if (!DB_Model::query("update news set date = :date where id = :id and type = 'news'")
            ->bind_values(['date' => $date, 'id' => $id])->execute()->ok) {
            DB_Model::roll_back();
            return $err_resp;
        }

        $existing = DB_Model::query("select lang_code from news_translations where news_id = :id")
            ->bind_values(['id' => $id])->fetch_all()->or_else([]);
        $existing_langs = array_column($existing, 'lang_code');

        $res = null;
        if (in_array('ru', $existing_langs)) {
            $res = DB_Model::query("update news_translations set title = :title, content = :content where news_id = :id and lang_code = 'ru'")
                ->bind_values(['title' => $title_ru, 'content' => $content_ru, 'id' => $id])->execute();
        } else {
            $res = DB_Model::query("insert into news_translations (news_id, lang_code, title, content) values (:id, 'ru', :title, :content)")
                ->bind_values(['id' => $id, 'title' => $title_ru, 'content' => $content_ru])->execute();
        }
        if (!$res->ok) {
            DB_Model::roll_back();
            return $err_resp;
        }
        if (in_array('en', $existing_langs)) {
            $res = DB_Model::query("update news_translations set title = :title, content = :content where news_id = :id and lang_code = 'en'")
                ->bind_values(['title' => $title_en, 'content' => $content_en, 'id' => $id])->execute();
        } else {
            $res = DB_Model::query("insert into news_translations (news_id, lang_code, title, content) values (:id, 'en', :title, :content)")
                ->bind_values(['id' => $id, 'title' => $title_en, 'content' => $content_en])->execute();
        }
        if (!$res->ok) {
            DB_Model::roll_back();
            return $err_resp;
        }
        DB_Model::commit();

        return Response::redirect('/news/' . $id);
    }

    public static function delete(Request $req): Response {
        DB_Model::begin_transaction();
        if (DB_Model::$current_db === DB_Type::SQLITE) {
            DB_Model::query('pragma foreign_keys = on')->execute();
        }
        $id = (int)$req->binds['id'];
        if (!DB_Model::query("delete from news where id = :id and type = 'news'")
            ->bind_values(['id' => $id])->execute()->ok) {
            DB_Model::roll_back();
            return Response::redirect('/news');
        }
        DB_Model::commit();

        return Response::redirect('/news');
    }
}

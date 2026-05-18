<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Helpers\Error;
use App\Core\Locale;
use App\Core\View\Js_Script;
use App\Core\View\View;
use App\Views\Common_View;
use App\Views\Static_Page;
use App\Models\User_Privileges;

final class Static_Pages {
    private function __construct() {}

    public static function about(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        $is_admin = !is_null($user) && $user->privilege === User_Privileges::ADMIN;
        $edit_text = Locale::get('about.edit_button');
        $edit_button = "<a href=\"/about/edit\" class=\"edit-page-button\">{$edit_text}</a><hr style=\"margin-top:30px;margin-bottom:0px;\">";

        $file_path = './public/about_us.html';
        if (!file_exists($file_path)) {
            $err = View::string('<p>Страница не найдена</p>');
            return Response::view(Common_View::layout($err, title: 'О нас', page_name: 'about', user: $user));
        }
        $content = file_get_contents($file_path);
        $content = "<div class=\"about-wrapper\">{$content}</div>";

        if ($is_admin) {
            $content = $edit_button . $content;
        }

        $comp = View::string($content);
        return Response::view(Common_View::layout($comp, title: 'О нас', page_name: 'about', user: $user));
    }

    public static function about_edit(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if (is_null($user) || $user->privilege !== User_Privileges::ADMIN) {
            return Response::redirect('/');
        }

        $file_path = './public/about_us.html';
        if (!file_exists($file_path)) {
            $err = View::string('<p>Страница не найдена</p>');
            return Response::view(Common_View::layout($err, title: 'О нас', page_name: 'about', user: $user));
        }

        $content = file_get_contents($file_path);
        $comp = Static_Page::edit_page($content, '/about', '/about/save');
        return Response::view(Common_View::layout($comp, title: 'О нас', page_name: 'about', user: $user, scripts: [
            Js_Script::from('//code.jquery.com/jquery-3.6.0.min.js'),
            Js_Script::from('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js'),
            Js_Script::from('https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js'),
        ]));
    }

    public static function about_save(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if (!$user || $user->privilege !== User_Privileges::ADMIN) {
            return Response::redirect('/');
        }

        $content = $req->form['content'] ?? '';
        $content = strip_tags($content, '<p><a><b><i><strong><em><ul><ol><li><br><img><h1><h2><h3><h4><span><div><table><tr><td><th><thead><tbody><tfoot><iframe>');

        $res = file_put_contents('public/about_us.html', $content);
        if ($res === false) {
            Error::assert(false, 'Не удалось сохранить файл about_us.html');
        }

        return Response::redirect('/about');
    }
}

<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Helpers\Error;
use App\Core\Locale;
use App\Core\View\Js_Script;
use App\Core\View\View;
use App\Views\Common_View;
use App\Views\Static_Page;
use App\Models\Dto\User_Privileges;

final class Static_Pages {
    private function __construct() {}

    public static function about(Request $req): Response {
        $__ = fn(string $key) => Locale::get('about.'.$key);
        $user = $req->additional['user'] ?? null;
        $is_admin = !is_null($user) && $user->privilege === User_Privileges::ADMIN;
        $edit_text = $__('edit_button');
        $edit_button = "<a href=\"/about/edit\" class=\"edit-page-button\">{$edit_text}</a><hr style=\"margin-top:30px;margin-bottom:0px;\">";

        $file_path = $__('file_path');

        if (!file_exists($file_path)) {
            $err = View::string("<p>{$__('not_found')}</p>");
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
        $user = $req->additional['user'];
        $__ = fn(string $key) => Locale::get('about.'.$key);
        $file_path = $__('file_path');
        if (!file_exists($file_path)) {
            $err = View::string("<p>{$__('not_found')}</p>");
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
        $content = $req->form['content'] ?? '';
        $content = strip_tags($content, '<p><a><b><i><strong><em><ul><ol><li><br><img><h1><h2><h3><h4><span><div><table><tr><td><th><thead><tbody><tfoot><iframe>');

        $res = file_put_contents(Locale::get('about.file_path'), $content);
        if ($res === false) {
            Error::assert(false, 'Не удалось сохранить файл about_us.html');
        }

        return Response::redirect('/about');
    }



    public static function site_scheme(Request $req): Response {
        $__ = fn(string $key) => Locale::get('site_scheme.'.$key);
        $user = $req->additional['user'] ?? null;
        $is_admin = !is_null($user) && $user->privilege === User_Privileges::ADMIN;
        $edit_text = $__('edit_button');
        $edit_button = "<a href=\"/site_scheme/edit\" class=\"edit-page-button\">{$edit_text}</a><hr style=\"margin-top:30px;margin-bottom:0px;\">";

        $file_path = $__('file_path');

        if (!file_exists($file_path)) {
            $err = View::string("<p>{$__('not_found')}</p>");
            return Response::view(Common_View::layout($err, title: 'Схема сайта', page_name: 'site_scheme', user: $user));
        }
        $content = file_get_contents($file_path);
        $content = "<div class=\"site-scheme-wrapper\">{$content}</div>";

        if ($is_admin) {
            $content = $edit_button . $content;
        }

        $comp = View::string($content);
        return Response::view(Common_View::layout($comp, title: 'Схема сайта', page_name: 'site_scheme', user: $user));
    }

    public static function site_scheme_edit(Request $req): Response {
        $user = $req->additional['user'];
        $__ = fn(string $key) => Locale::get('site_scheme.'.$key);
        $file_path = $__('file_path');
        if (!file_exists($file_path)) {
            $err = View::string("<p>{$__('not_found')}</p>");
            return Response::view(Common_View::layout($err, title: 'Схема сайта', page_name: 'site_scheme', user: $user));
        }

        $content = file_get_contents($file_path);
        $comp = Static_Page::edit_page($content, '/site_scheme', '/site_scheme/save');
        return Response::view(Common_View::layout($comp, title: 'Схема сайта', page_name: 'site_scheme', user: $user, scripts: [
            Js_Script::from('//code.jquery.com/jquery-3.6.0.min.js'),
            Js_Script::from('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js'),
            Js_Script::from('https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js'),
        ]));
    }

    public static function site_scheme_save(Request $req): Response {
        $content = $req->form['content'] ?? '';
        $content = strip_tags($content, '<p><a><b><i><strong><em><ul><ol><li><br><img><h1><h2><h3><h4><span><div><table><tr><td><th><thead><tbody><tfoot><iframe>');

        $res = file_put_contents(Locale::get('site_scheme.file_path'), $content);
        if ($res === false) {
            Error::assert(false, 'Не удалось сохранить файл site_scheme.html');
        }

        return Response::redirect('/site_scheme');
    }
}

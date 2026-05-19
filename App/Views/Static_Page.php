<?php namespace App\Views;

use App\Core\Locale;
use App\Core\View\Component;
use App\Core\View\View;

final class Static_Page {
    public static function edit_page(string $content, string $return_url, string $save_url): Component {
        return View::func(function () use ($content, $return_url, $save_url) {
            $__ = fn($key) => Locale::get('about.'.$key);
            return <<<HTML
                <form class="form" method="post" action="{$save_url}">
                    <textarea id="summernote" name="content">{$content}</textarea>
                    <button type="submit" class="form-submit">{$__('save_btn')}</button>
                    <a href="{$return_url}" class="form-cancel">{$__('cancel_btn')}</a>
                </form>
                <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" />
                <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.css" rel="stylesheet"/>
                <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script>
                <script>
                $(document).ready(function() {
                    $('#summernote').summernote({
                        height: 400,
                        lang: 'ru-RU'
                    });
                });
                </script>
                HTML;
        });
    }
}

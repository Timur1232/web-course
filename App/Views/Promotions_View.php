<?php namespace App\Views;

use App\Core\View\Component;
use App\Core\View\View;
use App\Core\Locale;
use App\Models\Dto\User;
use App\Models\Dto\User_Privileges;

final class Promotions_View {
    public static function list(array $items, ?User $user, int $page, int $total_pages, string $base_url): Component {
        return View::func(function () use ($items, $user, $page, $total_pages, $base_url) {
            $__ = fn($key) => Locale::get("promotions.{$key}");
            $is_admin = $user && $user->privilege === User_Privileges::ADMIN;
            $html = '<div class="promotions-list">';
            $html .= '<h2>' . $__('list_title') . '</h2>';

            if ($is_admin) {
                $html .= '<a href="/promotions/new" class="admin-add-btn">' . $__('add_button') . '</a>';
            }

            if (empty($items)) {
                $html .= '<p class="no-items">' . $__('no_promotions') . '</p>';
            } else {
                foreach ($items as $item) {
                    $html .= <<<HTML
                    <div class="promotion-item">
                        <h3><a href="/promotions/{$item->id}">{$item->title}</a></h3>
                        <span class="promotion-date">{$item->date}</span>
                        <p class="promotion-preview">{$item->preview}</p>
                    </div>
                    HTML;
                }

                if ($total_pages > 1) {
                    $html .= '<div class="pagination">';
                    $sep = strpos($base_url, '?') === false ? '?' : '&';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active = $i === $page ? ' class="active"' : '';
                        $html .= '<a href="' . $base_url . $sep . 'page=' . $i . '"' . $active . '>' . $i . '</a>';
                    }
                    $html .= '</div>';
                }
            }

            $html .= '</div>';
            return $html;
        });
    }

    public static function detail(object $promotion, ?User $user): Component {
        return View::func(function () use ($promotion, $user) {
            $__ = fn($key) => Locale::get("promotions.{$key}");
            $is_admin = $user && $user->privilege === User_Privileges::ADMIN;
            $id = (int)$promotion->id;

            $admin_buttons = '';
            if ($is_admin) {
                $admin_buttons = <<<HTML
                <div class="promotion-admin-actions">
                    <a href="/promotions/{$id}/edit" class="admin-edit-btn">{$__('edit_button')}</a>
                    <form method="post" action="/promotions/{$id}/delete" style="display:inline" onsubmit="return confirm('{$__('confirm_delete')}')">
                        <button type="submit" class="admin-delete-btn">{$__('delete_button')}</button>
                    </form>
                </div>
                HTML;
            }

            return <<<HTML
            <div class="promotion-detail">
                <a href="/promotions" class="back-link">{$__('back_to_list')}</a>
                {$admin_buttons}
                <h2>{$promotion->title}</h2>
                <span class="promotion-date">{$promotion->date}</span>
                <div class="promotion-content">{$promotion->content}</div>
            </div>
            HTML;
        });
    }

    public static function form(string $save_url, ?object $promotion = null): Component {
        return View::func(function () use ($save_url, $promotion) {
            $__ = fn($key) => Locale::get("promotions.{$key}");
            $is_edit = !is_null($promotion);
            $form_title = $is_edit ? $__('edit_title') : $__('create_title');

            $date_val = $is_edit ? $promotion->date ?? '' : date('Y-m-d');
            $title_ru_val = $is_edit ? $promotion->translations['ru']->title ?? '' : '';
            $preview_ru_val = $is_edit ? $promotion->translations['ru']->preview ?? '' : '';
            $content_ru_val = $is_edit ? $promotion->translations['ru']->content ?? '' : '';
            $title_en_val = $is_edit ? $promotion->translations['en']->title ?? '' : '';
            $preview_en_val = $is_edit ? $promotion->translations['en']->preview ?? '' : '';
            $content_en_val = $is_edit ? $promotion->translations['en']->content ?? '' : '';

            return <<<HTML
            <form class="form" method="post" action="{$save_url}">
                <h2 class="form-title">{$form_title}</h2>

                <label>{$__('promotion_date')}</label>
                <input type="date" name="date" value="{$date_val}" required>

                <label>{$__('field_title_ru')}</label>
                <input type="text" name="title_ru" value="{$title_ru_val}" required>
                <label>{$__('field_preview_ru')}</label>
                <input type="text" name="preview_ru" value="{$preview_ru_val}" maxlength="100">
                <label>{$__('field_content_ru')}</label>
                <textarea id="editor-ru" name="content_ru">{$content_ru_val}</textarea>

                <label>{$__('field_title_en')}</label>
                <input type="text" name="title_en" value="{$title_en_val}" required>
                <label>{$__('field_preview_en')}</label>
                <input type="text" name="preview_en" value="{$preview_en_val}" maxlength="100">
                <label>{$__('field_content_en')}</label>
                <textarea id="editor-en" name="content_en">{$content_en_val}</textarea>

                <button type="submit" class="form-submit">{$__('save')}</button>
                <a href="/promotions" class="form-cancel">{$__('cancel')}</a>
            </form>

            <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script>
            <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" />
            <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.css" rel="stylesheet"/>

            <script>
            $(document).ready(function() {
                $('#editor-ru').summernote({ height: 300, lang: 'ru-RU' });
                $('#editor-en').summernote({ height: 300, lang: 'en-US' });
            });
            </script>
            HTML;
        });
    }
}

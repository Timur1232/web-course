<?php namespace App\Views;

use App\Core\Locale;
use App\Core\View\Component_Func;
use App\Core\View\View;

final class Admin_View {
    private function __construct() {}

    public static function user_list(array $users): Component_Func {
        return View::func(function () use ($users): string {
            $list_items = '';
            foreach ($users as $user) {
                $list_items .= self::user_list_item($user);
            }
            $title = Locale::get('admin.title');
            return <<<HTML
                <h2>{$title}</h2>
                <div class="user-list">
                    {$list_items}
                </div>
                HTML;
        });
    }

    public static function user_list_item(array $user): string {
        $__ = fn($k) => Locale::get('admin.'.$k);
        $selected = fn($pr) => $pr === $user['privilege_name'] ? 'selected' : '';
        return <<<HTML
            <div class="user-list-item">
                <span class="user-list-item-login">{$user['login']}</span>
                <form method="post" action="/admin/users/update" class="user-privilege-form">
                    <input type="hidden" name="login" value="{$user['login']}">
                    <select name="privilege" onchange="this.form.submit()">
                        <option value="customer" {$selected('customer')}>{$__('pr_customer')}</option>
                        <option value="admin" {$selected('admin')}>{$__('pr_admin')}</option>
                    </select>
                </form>
                <form method="post" action="/admin/users/delete" class="user-delete-form"
                      onsubmit="return confirm('{$__('delete_confirm')}')">
                    <input type="hidden" name="login" value="{$user['login']}">
                    <button type="submit" class="delete-btn" title="Удалить">&#10005;</button>
                </form>
            </div>
            HTML;
    }
}

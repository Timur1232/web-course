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
            $add_button = Locale::get('admin.add_btn');
            return <<<HTML
                <h2>{$title}</h2>
                <a href="/admin/users/create" class="admin-add-btn">{$add_button}</a>
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

    public static function shortcut_links(): Component_Func {
        return View::func(function (): string {
            $__ = fn($k) => Locale::get('admin.'.$k);
            return <<<HTML
                <ul class="site-scheme">
                    <li><a href="/admin/orders">{$__('ln_orders')}</a></li>
                    <li><a href="/admin/callbacks">{$__('ln_callbacks')}</a></li>
                    <br>
                    <li><a href="/products/add">{$__('ln_add_product')}</a></li>
                    <li><a href="/products/add_category">{$__('ln_add_cat')}</a></li>
                    <br>
                    <li><a href="/news/new">{$__('ln_add_news')}</a></li>
                    <li><a href="/promotions/new">{$__('ln_add_prom')}</a></li>
                    <br>
                    <li><a href="/about/edit">{$__('ln_edit_about')}</a></li>
                    <li><a href="/site_scheme/edit">{$__('ln_edit_sitemap')}</a></li>
                </ul>
                <hr>
                HTML;
        });
    }

    public static function index(array $users): Component_Func {
        return View::func(function () use ($users): string {
            $html = self::shortcut_links()->render();
            $html .= self::user_list($users)->render();
            return $html;
        });
    }

    public static function user_form(?string $error = null): Component_Func {
        return View::func(function () use ($error): string {
            $__ = fn($key, $replace = []) => Locale::get("admin.{$key}", $replace);
            $error_html = $error ? '<div class="form-error">' . $error . '</div>' : '';
            return <<<HTML
                <form class="form" method="post" action="/admin/users/create">
                    <h2 class="form-title">{$__('user_form_title')}</h2>
                    {$error_html}
                    <label>{$__('user_form_login')}</label>
                    <input type="text" name="login" required>
                    <label>{$__('user_form_email')}</label>
                    <input type="email" name="email" required>
                    <label>{$__('user_form_password')}</label>
                    <input type="password" name="password" required>
                    <label>{$__('user_form_privilege')}</label>
                    <select name="privilege">
                        <option value="customer" selected>{$__('customer')}</option>
                        <option value="admin">{$__('admin')}</option>
                    </select>
                    <button class="form-submit" type="submit">{$__('submit_btn')}</button>
                    <a href="/admin" class="form-cancel" type="reset">{$__('reset_btn')}</a>
                </form>
            HTML;
        });
    }

    private static function order_list_item(object $order): string {
        return <<<HTML
            <div class="news-item">
                <h3>{$order->customer_name}</h3>
                <span class="news-date">{$order->date}</span>
                <p class="news-preview">Сумма: {$order->total} ₸</p>
                <p class="news-preview">Почта: <a href="mailto:{$order->email}">{$order->email}</a></p>
                <p class="news-preview">Телефон: {$order->phone}</p>
            </div>
            HTML;
    }

    public static function orders_list(array $orders): Component_Func {
        return View::func(function () use ($orders): string {
            $list_items = '';
            foreach ($orders as $order) {
                $list_items .= self::order_list_item($order);
            }
            $title = Locale::get('admin.orders_title');
            return <<<HTML
                <h2>{$title}</h2>
                <div class="orders-list">
                    {$list_items}
                </div>
                HTML;
        });
    }

    private static function callback_list_item(object $callback): string {
        return <<<HTML
            <div class="news-item">
                <h3>Имя: {$callback->name}</h3>
                <span class="news-date">{$callback->date}</span>
                <p class="news-preview">Почта: <a href="mailto:{$callback->email}">{$callback->email}</a></p>
                <p class="news-preview">Сообщение: {$callback->message}</p>
            </div>
            HTML;
    }

    public static function callbacks_list(array $callbacks): Component_Func {
        return View::func(function () use ($callbacks): string {
            $list_items = '';
            foreach ($callbacks as $order) {
                $list_items .= self::callback_list_item($order);
            }
            $title = Locale::get('admin.callbacks_title');
            return <<<HTML
                <h2>{$title}</h2>
                <div class="callbacks-list">
                    {$list_items}
                </div>
                HTML;
        });
    }
}

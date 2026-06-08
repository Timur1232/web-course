<?php namespace App\Views;

use App\Core\View\Component_Func;
use App\Core\View\View;
use App\Core\Locale;

final class User_Actions
{
    public static function login_form(?string $redirect_url = '', ?string $error = null): Component_Func {
        return View::func(function () use ($redirect_url, $error) {
            $__ = fn($key, $replace = []) => Locale::get("user_actions.{$key}", $replace);
            $redirect_attr = $redirect_url ?? '';
            $error_html = $error ? '<div class="form-error">' . $error . '</div>' : '';
            return <<<HTML
                <div class="auth-form">
                    <h2>{$__('login_title')}</h2>
                    {$error_html}
                    <form method="post" action="/login">
                        <input type="hidden" name="redirect_url" value="{$redirect_attr}">
                        <label>{$__('login_field')}</label>
                        <input type="text" name="login" required>
                        <label>{$__('password_field')}</label>
                        <input type="password" name="password" required>
                        <button type="submit">{$__('submit_login')}</button>
                    </form>
                    <p><a href="/register">{$__('link_register')}</a></p>
                </div>
            HTML;
        });
    }

    public static function register_form(?string $redirect_url = '', ?string $error = null): Component_Func {
        return View::func(function () use ($redirect_url, $error) {
            $__ = fn($key, $replace = []) => Locale::get("user_actions.{$key}", $replace);
            $redirect_attr = $redirect_url ?? '';
            $error_html = $error ? '<div class="form-error">' . $error . '</div>' : '';
            return <<<HTML
                <div class="auth-form">
                    <h2>{$__('register_title')}</h2>
                    {$error_html}
                    <form method="post" action="/register">
                        <input type="hidden" name="redirect_url" value="{$redirect_attr}">
                        <label>{$__('login_field')}</label>
                        <input type="text" name="login" required>
                        <label>{$__('email_field')}</label>
                        <input type="email" name="email" required>
                        <label>{$__('password_field')}</label>
                        <input type="password" name="password" required>
                        <label>{$__('password_confirm_field')}</label>
                        <input type="password" name="password_confirm" required>
                        <button type="submit">{$__('submit_register')}</button>
                    </form>
                    <p><a href="/login">{$__('link_login')}</a></p>
                </div>
            HTML;
        });
    }

    private function __construct() {}
}

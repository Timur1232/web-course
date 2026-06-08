<?php namespace App\Views;

use App\Core\View\Component;
use App\Core\View\View;
use App\Core\Locale;

final class Feedback_View {
    public static function feedback_form(?string $error = null): Component {
        return View::func(function () use ($error) {
            $__ = fn($key) => Locale::get("feedback.{$key}");
            $error_html = $error ? '<div class="form-error">' . $error . '</div>' : '';
            return <<<HTML
                <form class="form form-narrow" method="post" action="/feedback">
                    <h2 class="form-title">{$__('title')}</h2>
                    <p class="form-desctription">{$__('description')}</p>
                    {$error_html}
                    <label>{$__('name')}</label>
                    <input type="text" name="name" required>
                    <label>{$__('email')}</label>
                    <input type="email" name="email" required>
                    <label>{$__('message')}</label>
                    <textarea name="message" rows="6" required></textarea>
                    <button type="submit" class="form-submit">{$__('submit')}</button>
                </form>
            HTML;
        });
    }

    public static function thanks_message(): Component {
        return View::func(function () {
            return '<div class="feedback-thanks"><p>' . Locale::get('feedback.thanks') . '</p></div>';
        });
    }

    public static function error_message(): Component {
        return View::func(function () {
            return '<div class="feedback-error"><p>' . Locale::get('feedback.error') . '</p></div>';
        });
    }
}

<?php namespace App\Views;

use App\Core\View\Component;
use App\Core\View\View;
use App\Core\Locale;

final class Feedback_View {
    public static function feedback_form(?string $error = null): Component {
        return View::func(function () use ($error) {
            $__ = fn($key) => Locale::get("feedback.{$key}");
            $error_html = $error ? '<div class="form-error">' . htmlspecialchars($error) . '</div>' : '';
            return <<<HTML
            <div class="feedback-form">
                <h2>{$__('title')}</h2>
                <p>{$__('description')}</p>
                {$error_html}
                <form method="post" action="/feedback">
                    <label>{$__('name')}</label>
                    <input type="text" name="name" required>
                    <label>{$__('email')}</label>
                    <input type="email" name="email" required>
                    <label>{$__('message')}</label>
                    <textarea name="message" rows="6" required></textarea>
                    <button type="submit">{$__('submit')}</button>
                </form>
            </div>
            HTML;
        });
    }

    public static function thanks_message(): Component {
        return View::func(function () {
            $__ = fn($key) => Locale::get("feedback.{$key}");
            return '<div class="feedback-thanks"><p>' . htmlspecialchars($__('thanks')) . '</p></div>';
        });
    }
}

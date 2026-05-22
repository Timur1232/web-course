<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Locale;
use App\Core\Model\DB_Model;
use App\Views\Common_View;
use App\Views\Feedback_View;

final class Feedback {
    private function __construct() {}

    public static function index(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        $content = Feedback_View::feedback_form();
        return Response::view(Common_View::layout(
            $content,
            title: Locale::get('feedback.title'),
            page_name: 'feedback',
            user: $user
        ));
    }

    public static function send(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        $name = trim($req->form['name'] ?? '');
        $email = trim($req->form['email'] ?? '');
        $message = trim($req->form['message'] ?? '');

        if ($name === '' || $email === '' || $message === '') {
            $content = Feedback_View::feedback_form(Locale::get('feedback.error_missing'));
            return Response::view(Common_View::layout(
                $content,
                title: Locale::get('feedback.title'),
                page_name: 'feedback',
                user: $user
            ));
        }

        DB_Model::query("
            insert into callback_messages (name, email, message)
            values (:name, :email, :message)
        ")->bind_values([
            'name' => $name,
            'email' => $email,
            'message' => $message,
        ])->execute();

        $content = Feedback_View::thanks_message();
        return Response::view(Common_View::layout(
            $content,
            title: Locale::get('feedback.title'),
            page_name: 'feedback',
            user: $user
        ));
    }
}

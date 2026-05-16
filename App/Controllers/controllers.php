<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\View\View;

final class Index {
    private function __construct() {}

    public static function index(Request $req): Response {
        return Response::view(View::func(function() {
            return <<<HTML
                <h1>Hello</h1>
                HTML;
        }));
    }
}

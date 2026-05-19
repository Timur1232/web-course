<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Locale;
use App\Core\Model\DB_Model;
use App\Models\User_Privileges;
use App\Views\Admin_View;
use App\Views\Common_View;

final class Admin {
    private function __construct() {}

    public static function index(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if (is_null($user) || $user->privilege !== User_Privileges::ADMIN) {
            return Response::redirect('/login');
        }

        $users = DB_Model::query("
            select u.login, up.privilege_name
            from users u
            join user_privileges up on up.user_login = u.login
            where u.login != :login
            order by u.login
            ")?->bind_values(['login' => $user->login])
            ?->execute()
            ?->fetch_all();

        if (is_null($users)) {
            return Response::redirect('/');
        }

        $__ = fn($k) => Locale::get('admin.'.$k);
        $comp = Admin_View::user_list($users);
        return Response::view(Common_View::layout(
            $comp,
            title: $__('page_title'),
            page_name: 'admin',
            user: $user
        ));
    }

    public static function update(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if (is_null($user) || $user->privilege !== User_Privileges::ADMIN) {
            return Response::redirect('/login');
        }

        $login = $req->form['login'] ?? null;
        $privilege = $req->form['privilege'] ?? null;

        if (is_null($login) || is_null($privilege)) {
            return Response::redirect('/admin');
        }

        $privilege = User_Privileges::tryFrom($privilege);
        if (is_null($privilege)) {
            return Response::redirect('/admin');
        }

        DB_Model::query('update user_privileges set privilege_name = :pr where user_login = :login')?->bind_values(['pr' => $privilege->value, 'login' => $login])
            ?->execute();

        return Response::redirect('/admin');
    }

    public static function delete(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if (is_null($user) || $user->privilege !== User_Privileges::ADMIN) {
            return Response::redirect('/login');
        }

        $login = $req->form['login'] ?? null;
        if (is_null($login) || $login === $user->login) {
            return Response::redirect('/admin');
        }

        if (\Config::IS_USING_SQLITE) {
            DB_Model::query('pragma foreign_keys = on')?->execute();
        }
        DB_Model::query('delete from users where login = :login')
            ?->bind_values(['login' => $login])
            ?->execute();

        return Response::redirect('/admin');
    }
}

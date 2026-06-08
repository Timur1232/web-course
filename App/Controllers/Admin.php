<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Locale;
use App\Core\Model\DB_Model;
use App\Core\Model\DB_Type;
use App\Models\Common_Sql\Common_Sql;
use App\Models\Dto\User;
use App\Models\Dto\User_Privilege;
use App\Models\Dto\User_Privileges;
use App\Views\Admin_View;
use App\Views\Common_View;

final class Admin {
    private function __construct() {}

    public static function index(Request $req): Response {
        $user = $req->additional['user'];
        $users = DB_Model::query("
            select u.login, up.privilege_name
            from users u
            join user_privileges up on up.user_login = u.login
            where u.login != :login
            order by u.login
            ")->bind_values(['login' => $user->login])
            ->fetch_all();

        if (!$users->ok) {
            return Response::redirect('/');
        }

        return Response::view(Common_View::layout(
            Admin_View::index($users->val),
            title: Locale::get('admin.page_title'),
            page_name: 'admin',
            user: $user
        ));
    }

    public static function update(Request $req): Response {
        $login = $req->form['login'];
        $privilege = $req->form['privilege'] ?? null;

        if (is_null($login) || is_null($privilege)) {
            return Response::redirect('/admin');
        }

        $privilege = User_Privileges::tryFrom($privilege);
        if (is_null($privilege)) {
            return Response::redirect('/admin');
        }

        $sql = 'update user_privileges set privilege_name = :pr where user_login = :login';
        DB_Model::begin_transaction();
        if (!DB_Model::query($sql)->bind_values(['pr' => $privilege->value, 'login' => $login])->execute()->ok) {
            DB_Model::roll_back();
        } else {
            DB_Model::commit();
        }

        return Response::redirect('/admin');
    }

    public static function delete(Request $req): Response {
        $user = $req->additional['user'];
        $login = $req->form['login'] ?? null;
        if (is_null($login) || $login === $user->login) {
            return Response::redirect('/admin');
        }

        DB_Model::begin_transaction();
        if (DB_Model::$current_db === DB_Type::SQLITE) {
            DB_Model::query('pragma foreign_keys = on')->execute();
        }
        if (!DB_Model::query('delete from users where login = :login')
            ->bind_values(['login' => $login])
            ->execute()->ok) {
            DB_Model::roll_back();
        } else {
            DB_Model::commit();
        }

        return Response::redirect('/admin');
    }

    public static function user_form(Request $req): Response {
        $user = $req->additional['user'];
        $comp = Admin_View::user_form();
        return Response::view(Common_View::layout($comp, title: 'Добавление пользователя', page_name: 'admin', user: $user));
    }

    public static function user_create(Request $req): Response {
        $user = $req->additional['user'];
        $login = $req->form['login'] ?? null;
        $email = $req->form['email'] ?? null;
        $password = $req->form['password'] ?? null;
        $privilege = $req->form['privilege'] ?? null;

        $err_func = function (string $locale) use ($user): Response {
            $err = Locale::get('admin.'.$locale);
            $comp = Admin_View::user_form($err);
            return Response::view(Common_View::layout($comp, title: 'Добавление пользователя', page_name: 'admin', user: $user));
        };

        if (is_null($login) || is_null($email) || is_null($password) || is_null($privilege)) {
            return $err_func('invalid_data_err');
        }
        if (is_null(User_Privileges::tryFrom($privilege))) {
            return $err_func('invalid_data_err');
        }

        $password_hash = md5($password);
        $new_user = new User(
            login: $login,
            email: $email,
            password_hash: $password_hash,
        );

        $res = DB_Model::query(Common_Sql::select(['login'], table: 'users', where: 'login = ?'))
            ->bind_values([$login])
            ->fetch();
        if ($res->ok) {
            return $err_func('exists_err');
        }

        DB_Model::begin_transaction();
        $res = DB_Model::query(Common_Sql::insert(User::class))
            ->bind_values($new_user)
            ->execute();
        if (!$res->ok) {
            DB_Model::roll_back();
            return $err_func('insert_err');
        }
        $res = DB_Model::query(Common_Sql::insert(User_Privilege::class))
            ->bind_values(new User_Privilege(
                user_login: $login,
                privilege_name: $privilege,
            ))->execute();
        if (!$res->ok) {
            DB_Model::roll_back();
            return $err_func('insert_err');
        }
        DB_Model::commit();

        return Response::redirect('/admin');
    }

    public static function orders(Request $req): Response {
        $user = $req->additional['user'] ?? null;

        $sql = "
            select * from orders
            order by date desc
        ";

        $rows = DB_Model::query($sql)->fetch_all()->or_else([]);
        $items = array_map(fn($v) => (object)$v, $rows);

        $comp = Admin_View::orders_list($items);
        $title = Locale::get('admin.orders_title');
        return Response::view(Common_View::layout($comp, title: $title, page_name: 'orders', user: $user));
    }

    public static function callbacks(Request $req): Response {
        $user = $req->additional['user'] ?? null;

        $sql = "
            select * from callback_messages
            order by date desc
        ";
        $rows = DB_Model::query($sql)->fetch_all()->or_else([]);
        $items = array_map(fn($v) => (object)$v, $rows);

        $comp = Admin_View::callbacks_list($items);
        $title = Locale::get('admin.callback_title');
        return Response::view(Common_View::layout($comp, title: $title, page_name: 'callbacks', user: $user));
    }
}

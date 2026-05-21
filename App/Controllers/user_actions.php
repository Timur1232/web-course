<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\View\View;
use App\Views\Common_View;
use App\Core\Model\DB_Model;
use App\Core\Model\AR_Reflect;
use App\Core\Locale;
use App\Jwt_Token;
use App\Models\Common_Sql;
use App\Models\User;
use App\Models\User_Privileges;

final class User_Actions {
    private function __construct() {}

    public static function login_form(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if ($user) {
            return Response::redirect('/');
        }
        $current_path = urldecode($req->url->query['redirect'] ?? '/') ?? '/';
        return Response::view(Common_View::layout(
            \App\Views\User_Actions::login_form($current_path),
            title: Locale::get('user_actions.login_title'),
            page_name: 'login',
            user: null
        ));
    }

    public static function login_post(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if ($user) {
            return Response::redirect('/');
        }
        $login = $req->form['login'] ?? null;
        $password = $req->form['password'] ?? null;
        $redirect_url = $req->form['redirect_url'] ?? '/';
        if (empty($login) || empty($password)) {
            return self::login_error($redirect_url, Locale::get('user_actions.error_missing_fields'));
        }

        $stmt = DB_Model::query(Common_Sql::select(User::class, where: 'login = :login'))
            ?->bind_values(['login' => $login])
            ?->execute();
        if (!$stmt) {
            return self::login_error($redirect_url, Locale::get('user_actions.error_invalid_credentials'));
        }
        $user_data = $stmt->fetch();
        if (!$user_data || md5($password) !== ($user_data['password_hash'] ?? '')) {
            return self::login_error($redirect_url, Locale::get('user_actions.error_invalid_credentials'));
        }

        $priv_stmt = DB_Model::query("select privilege_name from user_privileges where user_login = :login")
            ?->bind_values(['login' => $login])
            ?->execute();
        $privilege = User_Privileges::CUSTOMER;
        if ($priv_stmt) {
            foreach ($priv_stmt->fetch_all() as $row) {
                if ($row['privilege_name'] === User_Privileges::ADMIN->value) {
                    $privilege = User_Privileges::ADMIN;
                    break;
                }
            }
        }

        $user_obj = AR_Reflect::construct(User::class, $user_data);
        $user_obj->privilege = $privilege;

        $jwt = Jwt_Token::generate_jwt($user_obj);
        setcookie('jwt_token', $jwt, time() + 86400 * 30, '/');
        return Response::redirect($redirect_url ?: '/');
    }

    public static function logout(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if (!$user) {
            return Response::redirect('/');
        }
        setcookie('jwt_token', '', time() - 3600, '/');
        if ($req->htmx) {
            $comp = View::func(function () {
                return '<a href="/login" class="login-button">' . htmlspecialchars(Locale::get('layout.login')) . '</a>';
            });
            return Response::view($comp);
        }
        return Response::redirect('/');
    }

    public static function register_form(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if ($user) {
            return Response::redirect('/');
        }
        $current_path = urldecode($req->url->query['redirect'] ?? '/') ?? '/';
        return Response::view(Common_View::layout(
            \App\Views\User_Actions::register_form($current_path),
            title: Locale::get('user_actions.register_title'),
            page_name: 'register',
            user: null
        ));
    }

    public static function register_post(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if ($user) {
            return Response::redirect('/');
        }
        $login = $req->form['login'] ?? null;
        $email = $req->form['email'] ?? null;
        $password = $req->form['password'] ?? null;
        $password_confirm = $req->form['password_confirm'] ?? null;
        $redirect_url = $req->form['redirect_url'] ?? '/';

        if (empty($login) || empty($email) || empty($password) || empty($password_confirm)) {
            return self::register_error($redirect_url, Locale::get('user_actions.error_missing_fields'));
        }
        if ($password !== $password_confirm) {
            return self::register_error($redirect_url, Locale::get('user_actions.error_passwords_mismatch'));
        }

        $exists = DB_Model::query(Common_Sql::select(User::class, where: 'login = :login'))
            ?->bind_values(['login' => $login])
            ?->execute();
        if ($exists && $exists->fetch()) {
            return self::register_error($redirect_url, Locale::get('user_actions.error_login_exists'));
        }

        $password_hash = md5($password);
        $insert_ok = DB_Model::query(Common_Sql::insert(User::class))
            ?->bind_values([
                'login'         => $login,
                'email'         => $email,
                'password_hash' => $password_hash,
            ])
            ?->execute();
        if (!$insert_ok) {
            return self::register_error($redirect_url, 'Ошибка сервера');
        }

        DB_Model::query("insert into user_privileges (user_login, privilege_name) values (:login, :priv)")
            ?->bind_values(['login' => $login, 'priv' => User_Privileges::CUSTOMER->value])
            ?->execute();

        $new_user = new User(login: $login, email: $email, password_hash: $password_hash, privilege: User_Privileges::CUSTOMER);
        $jwt = Jwt_Token::generate_jwt($new_user);
        setcookie('jwt_token', $jwt, time() + 86400 * 30, '/');
        return Response::redirect($redirect_url ?: '/');
    }

    private static function login_error(string $redirect_url, string $error): Response {
        return Response::view(Common_View::layout(
            \App\Views\User_Actions::login_form($redirect_url, error: $error),
            title: Locale::get('user_actions.login_title'),
            page_name: 'login',
            user: null
        ));
    }

    private static function register_error(string $redirect_url, string $error): Response {
        return Response::view(Common_View::layout(
            \App\Views\User_Actions::register_form($redirect_url, error: $error),
            title: Locale::get('user_actions.register_title'),
            page_name: 'register',
            user: null
        ));
    }
}

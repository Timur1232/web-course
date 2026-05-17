<?php namespace App\Middleware;
use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Locale;
use App\Jwt_Token;
use App\Core\Middleware;
use Closure;

final class User_Auth implements Middleware {
    public function apply(Closure $next): Closure {
        return function (Request $req) use($next): Response {
            $jwt = $req->cookies['jwt_token'] ?? null;
            $user = Jwt_Token::get_user_from_jwt($jwt);
            if (is_null($user)) {
                return Response::redirect('/login');
            }
            return $next($req);
        };
    }
}

final class Get_User implements Middleware {
    public function apply(Closure $next): Closure {
        return function (Request $req) use($next): Response {
            $jwt = $req->cookies['jwt_token'] ?? null;
            $user = Jwt_Token::get_user_from_jwt($jwt);
            if (!is_null($user)) {
                $req->additional['user'] = $user;
            }
            return $next($req);
        };
    }
}

final class Set_Language implements Middleware {
    public function apply(Closure $next): Closure {
        return function (Request $req) use($next): Response {
            $lang = $req->cookies['lang'] ?? 'ru';
            Locale::set_language($lang);
            return $next($req);
        };
    }
}

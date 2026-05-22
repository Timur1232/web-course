<?php namespace App;
use \Config;
use App\Core\Model\AR_Reflect;
use App\Core\Model\DB_Model;
use App\Models\Common_Sql\Common_Sql;
use App\Models\Dto\User;
use App\Models\Dto\User_Privileges;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

final class Jwt_Token {
    public static function generate_jwt(User $user): string {
        $key = Config::JWT_SECRET_KEY;
        $payload = [
            'iss' => 'example.org',
            'aud' => 'example.com',
            'iat' => 1356999524,
            'nbf' => 1357000000,
            'user_login' => $user->login,
            'user_privilege' => $user->privilege,
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    public static function get_user_from_jwt(?string $jwt): ?User {
        if (is_null($jwt)) return null;
        $headers = new stdClass();
        $decoded = JWT::decode($jwt, new Key(Config::JWT_SECRET_KEY, 'HS256'), $headers);
        $user_login = $decoded->user_login;
        $res = DB_Model::query(Common_Sql::select(User::class, where: "login = ?"))
            ->bind_values([$user_login])
            ->fetch();
        if (!$res->ok) return null;
        $user = AR_Reflect::construct(User::class, $res->val);
        $user->privilege = User_Privileges::from($decoded->user_privilege);
        return $user;
    }
}

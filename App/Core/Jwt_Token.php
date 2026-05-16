<?php namespace App\Core;
use App\Config;
use App\Core\Model\DBModel;
use App\Models\User;
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
            'is_admin' => $user->is_admin,
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    public static function get_user_from_jwt(?string $jwt): ?User {
        if (is_null($jwt)) return null;
        $headers = new stdClass();
        $decoded = JWT::decode($jwt, new Key(Config::JWT_SECRET_KEY, 'HS256'), $headers);
        $user_login = $decoded->user_login;
        $model = DBModel::sqlite(Config::SQLITE_DB_PATH);
        $res = $model->find_by_id(User::class, $user_login);
        return $res->val ?? null;
    }
}

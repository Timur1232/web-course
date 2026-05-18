<?php
require_once('./init.php');
require_once(__DIR__.'/vendor/autoload.php');
spl_autoload_register(Init::autoload(...));

if (!defined('STDIN'))  define('STDIN',  fopen('php://stdin', 'rb'));
if (!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));

function __(string $key, array $replace = []): string {
    return \App\Core\Locale::get($key, $replace);
}

session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

use App\Controllers\Products;
use App\Core\Context\Router;
use App\Core\Model\DB_Model;
use App\Middleware\Get_User;
use App\Middleware\Set_Language;
use \App\Controllers\User_Actions;
use \App\Controllers\Cart;
use \App\Controllers\Feedback;

DB_Model::sqlite_connect('test.db');
Router::setup_current_request();
Router::$global_middleware = [
    Get_User::class,
    Set_Language::class,
];

Router::GET('/', Products::index(...));
Router::GET('/products', Products::index(...));
Router::GET('/products/:id', Products::show(...));
Router::POST('/products/:id/review', Products::add_review(...));

Router::GET('/cart', Cart::index(...));
Router::POST('/cart/add', Cart::add(...));
Router::POST('/cart/update', Cart::update_quantity(...));
Router::POST('/cart/remove', Cart::remove(...));
Router::POST('/cart/checkout', Cart::checkout(...));

Router::GET('/login', User_Actions::login_form(...));
Router::POST('/login', User_Actions::login_post(...));
Router::POST('/logout', User_Actions::logout(...));
Router::GET('/register', User_Actions::register_form(...));
Router::POST('/register', User_Actions::register_post(...));

Router::GET('/feedback', Feedback::index(...));
Router::POST('/feedback', Feedback::send(...));

Router::GET('/map', \App\Controllers\Map::index(...));



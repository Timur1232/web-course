<?php
require_once('./init.php');
require_once(__DIR__.'/vendor/autoload.php');
spl_autoload_register(Init::autoload(...));

if (!defined('STDIN'))  define('STDIN',  fopen('php://stdin', 'rb'));
if (!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));

session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

use App\Controllers\Admin;
use App\Controllers\Products;
use App\Core\Context\Router;
use App\Core\Model\DB_Model;
use App\Middleware\Get_User;
use App\Middleware\Set_Language;
use \App\Controllers\User_Actions;
use \App\Controllers\Cart;
use \App\Controllers\Feedback;
use \App\Controllers\News;
use \App\Controllers\Static_Pages;
use \App\Controllers\Promotions;
use \App\Controllers\Categories;
use App\Middleware\Require_Admin;
use App\Middleware\User_Auth;

if (php_sapi_name() === 'cli-server') {
    DB_Model::sqlite_connect('test.db');
} else {
    DB_Model::my_sql_connect(
        host: Config::MYSQL_HOST,
        port: Config::MYSQL_PORT,
        db_name: Config::MYSQL_DB,
        username: Config::MYSQL_USER,
        password: Config::MYSQL_PASSWORD,
        options: [
            Pdo\Mysql::ATTR_DEFAULT_FETCH_MODE => Pdo\Mysql::FETCH_ASSOC,
            Pdo\Mysql::ATTR_EMULATE_PREPARES   => false,
        ],
    );
}
Router::setup_current_request();
Router::$global_middleware = [
    Get_User::class,
    Set_Language::class,
];

Router::GET('/', Products::index(...));
Router::GET('/products', Products::index(...));
Router::POST('/products/delete/:id', Products::delete(...));
Router::GET('/products/search', Products::index(...));

$products_admin = Router::group('/products', middleware: [
    Require_Admin::class,
]);

$products_admin->GET('/add', Products::add_form(...));
$products_admin->POST('/add', Products::create(...));

$products_admin->GET('/add_category', Categories::add_form(...));
$products_admin->POST('/add_category', Categories::create(...));
$products_admin->GET('/edit_category/:id', Categories::edit_form(...));
$products_admin->POST('/edit_category/:id', Categories::update(...));
$products_admin->GET('/delete_category/:id', Categories::delete(...));

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

$feedback = Router::group('/feedback', middleware: [
    User_Auth::class,
]);
$feedback->GET('/', Feedback::index(...));
$feedback->POST('/', Feedback::send(...));

Router::GET('/map', \App\Controllers\Map::index(...));

Router::GET('/about', Static_Pages::about(...));
$about_admin = Router::group('/about', middleware: [
    Require_Admin::class,
]);
$about_admin->GET('/edit', Static_Pages::about_edit(...));
$about_admin->POST('/save', Static_Pages::about_save(...));

Router::GET('/site_scheme', Static_Pages::site_scheme(...));
$scheme_admin = Router::group('/site_scheme', middleware: [
    Require_Admin::class,
]);
$scheme_admin->GET('/edit', Static_Pages::site_scheme_edit(...));
$scheme_admin->POST('/save', Static_Pages::site_scheme_save(...));

$news_admin = Router::group('/news', middleware: [
    Require_Admin::class,
]);
$news_admin->GET('/new', News::new(...));
$news_admin->POST('/new', News::create(...));
$news_admin->GET('/:id/edit', News::edit(...));
$news_admin->POST('/:id/edit', News::update(...));
$news_admin->POST('/:id/delete', News::delete(...));
Router::GET('/news', News::index(...));
Router::GET('/news/:id', News::show(...));

$promotions_admin = Router::group('/promotions', middleware: [
    Require_Admin::class,
]);
$promotions_admin->GET('/new', Promotions::new(...));
$promotions_admin->POST('/new', Promotions::create(...));
$promotions_admin->GET('/:id/edit', Promotions::edit(...));
$promotions_admin->POST('/:id/edit', Promotions::update(...));
$promotions_admin->POST('/:id/delete', Promotions::delete(...));
Router::GET('/promotions', Promotions::index(...));
Router::GET('/promotions/:id', Promotions::show(...));

$admin = Router::group('/admin', middleware: [
    Require_Admin::class
]);
$admin->GET('/', Admin::index(...));
$admin->POST('/users/update', Admin::update(...));
$admin->POST('/users/delete', Admin::delete(...));
$admin->GET('/users/create', Admin::user_form(...));
$admin->POST('/users/create', Admin::user_create(...));
$admin->GET('/orders', Admin::orders(...));
$admin->GET('/callbacks', Admin::callbacks(...));

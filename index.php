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
use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\View\View;
use \App\Controllers\News;
use \App\Controllers\Static_Pages;
use \App\Controllers\Promotions;
use \App\Controllers\Categories;

$conn_str = 'host=' . Config::MYSQL_HOST . ';port=3306;dbname=' . Config::MYSQL_DB;
DB_Model::my_sql_connect(
        $conn_str,
        Config::MYSQL_USER,
        Config::MYSQL_PASSWORD,
        [
            Pdo\Mysql::ATTR_DEFAULT_FETCH_MODE => Pdo\Mysql::FETCH_ASSOC,
            Pdo\Mysql::ATTR_EMULATE_PREPARES   => false,
        ],
);
Router::setup_current_request();
Router::$global_middleware = [
    Get_User::class,
    Set_Language::class,
];

Router::GET('/', Products::index(...));
Router::GET('/products', Products::index(...));
Router::POST('/products/delete/:id', Products::delete(...));
Router::GET('/products/search', Products::index(...));

Router::GET('/products/add', Products::add_form(...));
Router::POST('/products/add', Products::create(...));

Router::GET('/products/add_category', Categories::add_form(...));
Router::POST('/products/add_category', Categories::create(...));
Router::GET('/products/edit_category/:id', Categories::edit_form(...));
Router::POST('/products/edit_category/:id', Categories::update(...));
Router::GET('/products/delete_category/:id', Categories::delete(...));

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

Router::GET('/about', Static_Pages::about(...));
Router::GET('/about/edit', Static_Pages::about_edit(...));
Router::POST('/about/save', Static_Pages::about_save(...));

Router::GET('/site_scheme', Static_Pages::site_scheme(...));
Router::GET('/site_scheme/edit', Static_Pages::site_scheme_edit(...));
Router::POST('/site_scheme/save', Static_Pages::site_scheme_save(...));

Router::GET('/news', News::index(...));
Router::GET('/news/new', News::new(...));
Router::POST('/news/new', News::create(...));
Router::GET('/news/:id', News::show(...));
Router::GET('/news/:id/edit', News::edit(...));
Router::POST('/news/:id/edit', News::update(...));
Router::POST('/news/:id/delete', News::delete(...));

Router::GET('/promotions', Promotions::index(...));
Router::GET('/promotions/new', Promotions::new(...));
Router::POST('/promotions/new', Promotions::create(...));
Router::GET('/promotions/:id', Promotions::show(...));
Router::GET('/promotions/:id/edit', Promotions::edit(...));
Router::POST('/promotions/:id/edit', Promotions::update(...));
Router::POST('/promotions/:id/delete', Promotions::delete(...));

Router::GET('/admin', Admin::index(...));
Router::POST('/admin/users/update', Admin::update(...));
Router::POST('/admin/users/delete', Admin::delete(...));

Router::GET('/test', function (Request $req) {
    return Response::view(View::template('test'));
});
Router::POST('/test', function (Request $req) {
    return Response::view(View::string($req->form['text']));
});


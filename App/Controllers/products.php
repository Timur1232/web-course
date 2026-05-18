<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Model\DB_Model;
use App\Core\Locale;
use App\Core\View\View;
use App\Views\Common_View;
use App\Views\Products as Products_View;
use \App\Core\Helpers\Paginator;

final class Products {
    private function __construct() {}

    public static function index(Request $req): Response {
        $lang = Locale::get_language();
        $category_id = isset($req->url->query['category']) ? (int)$req->url->query['category'] : null;
        $page = max(1, (int)($req->url->query['page'] ?? 1));
        $per_page = 8;

        $cat_rows = DB_Model::query("
            select c.id, ct.name
            from categories c
            join category_translations ct on c.id = ct.category_id and ct.lang_code = :lang
            order by ct.name
        ")?->bind_values(['lang' => $lang])?->execute()?->fetch_all() ?: [];
        $categories = array_map(fn($r) => (object)$r, $cat_rows);

        $where_category = is_null($category_id) ? '' : ' and p.category_id = :cat_id ';
        $sql = "
            select p.id, pt.name, p.price,
                   (select image_url from product_images where product_id = p.id order by number asc limit 1) as image_url
            from products p
            join product_translations pt on p.id = pt.product_id and pt.lang_code = :lang
            where p.visible = 1 $where_category
            order by p.id
        ";
        $stmt = DB_Model::query($sql);
        $bind = ['lang' => $lang];
        if (!is_null($category_id)) $bind['cat_id'] = $category_id;
        $stmt?->bind_values($bind);
        $all_rows = $stmt?->execute()?->fetch_all() ?: [];

        $all_products = array_map(fn($row) => (object)$row, $all_rows);

        $per_page = 8;
        $paginator = new Paginator($all_products, $per_page);
        $total_pages = $paginator->page_count();
        $page = max(1, min($page, $total_pages));
        $current_page_items = $paginator->nth_page($page - 1) ?: [];

        $base_url = '/products';
        if (!is_null($category_id)) $base_url .= '?category=' . $category_id;

        $cart_ids = array_keys($_SESSION['cart'] ?? []);

        $content = View::func(function () use ($categories, $category_id, $current_page_items, $page, $total_pages, $base_url, $cart_ids) {
            $left = Products_View::category_panel($categories, $category_id)->render();
            $grid = Products_View::product_grid($current_page_items, $cart_ids)->render();
            $pagination = Products_View::pagination($page, $total_pages, $base_url)->render();
            return <<<HTML
                <div class="catalog-layout">
                    <aside class="catalog-sidebar">{$left}</aside>
                    <div class="catalog-main">
                        {$grid}
                        {$pagination}
                    </div>
                </div>
            HTML;
        });

        $user = $req->additional['user'] ?? null;
        return Response::view(Common_View::layout(
            $content,
            title: 'Товары',
            page_name: 'products',
            user: $user
        ));
    }

    public static function show(Request $req): Response {
        $lang = Locale::get_language();
        $id = (int)$req->binds['id'];
        $user = $req->additional['user'] ?? null;

        $stmt = DB_Model::query("
            select p.id, pt.name, pt.description, p.price
            from products p
            join product_translations pt on p.id = pt.product_id and pt.lang_code = :lang
            where p.id = :id and p.visible = 1
            ")?->bind_values(['lang' => $lang, 'id' => $id])?->execute();

        if (!$stmt || !($row = $stmt->fetch())) {
            return Response::redirect('/products');
        }

        $images = DB_Model::query("
            select image_url from product_images where product_id = :id order by number asc
            ")?->bind_values(['id' => $id])?->execute()?->fetch_all() ?: [];

        $reviews_rows = DB_Model::query("
            SELECT author_name, rating, date, text
            FROM reviews
            WHERE product_id = :id
            ORDER BY date DESC
            ")?->bind_values(['id' => $id])?->execute()?->fetch_all() ?: [];
        $reviews = array_map(fn($r) => (object)$r, $reviews_rows);

        $avg_row = DB_Model::query("
            SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = :id
            ")?->bind_values(['id' => $id])?->execute()?->fetch();
        $avg_rating = $avg_row ? round((float)($avg_row['avg_rating'] ?? 0), 1) : null;

        $product = (object)$row;
        $product->images = array_map(fn($r) => (object)$r, $images);

        $cart_ids = array_keys($_SESSION['cart'] ?? []);
        $content = Products_View::product_detail($product, $cart_ids, $user, $reviews, $avg_rating);

        return Response::view(Common_View::layout(
            $content,
            title: $product->name,
            page_name: 'products',
            user: $user
        ));
    }

    public static function add_review(Request $req): Response {
        $user = $req->additional['user'] ?? null;
        if (!$user) {
            $redirect = urlencode($req->url->path);
            return Response::redirect('/login?redirect=' . $redirect);
        }

        $id = (int)$req->binds['id'];
        $rating = (int)($req->form['rating'] ?? 0);
        $text = trim($req->form['text'] ?? '');

        if ($rating < 1 || $rating > 5 || empty($text)) {
            return Response::redirect("/products/{$id}#reviews");
        }

        DB_Model::query("insert into reviews (product_id, author_name, text, rating) values (:pid, :author, :text, :rating)")
            ?->bind_values([
                'pid' => $id,
                'author' => $user->login,
                'text' => $text,
                'rating' => $rating,
            ])
            ?->execute();

        return Response::redirect("/products/{$id}#reviews");
    }
}


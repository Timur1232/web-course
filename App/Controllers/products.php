<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Model\DB_Model;
use App\Core\Locale;
use App\Core\View\View;
use App\Views\Common_View;
use App\Views\Products as ProductsView;
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
            $left = ProductsView::category_panel($categories, $category_id)->render();
            $grid = ProductsView::product_grid($current_page_items, $cart_ids)->render();
            $pagination = ProductsView::pagination($page, $total_pages, $base_url)->render();
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

        $product = (object)$row;
        $product->images = array_map(fn($r) => (object)$r, $images);

        $cart_ids = array_keys($_SESSION['cart'] ?? []);
        $content = ProductsView::product_detail($product, $cart_ids);

        $user = $req->additional['user'] ?? null;
        return Response::view(Common_View::layout(
            $content,
            title: $product->name,
            page_name: 'products',
            user: $user
        ));
    }
}


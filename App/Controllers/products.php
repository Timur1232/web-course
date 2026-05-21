<?php namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Helpers\Log;
use App\Core\Model\DB_Model;
use App\Core\Locale;
use App\Core\View\View;
use App\Views\Common_View;
use App\Views\Products as Products_View;
use \App\Core\Helpers\Paginator;
use App\Core\View\Js_Script;
use App\Models\User_Privileges;

final class Products {
    private function __construct() {}

    public static function index(Request $req): Response {
        $lang = Locale::get_language();
        $category_id = isset($req->url->query['category']) ? (int)$req->url->query['category'] : null;
        $page = max(1, (int)($req->url->query['page'] ?? 1));
        $per_page = 8;
        $user = $req->additional['user'];
        $is_admin = (isset($user) && $user->privilege === User_Privileges::ADMIN);

        $search = $req->form['search'] ?? null;

        $cat_rows = DB_Model::query("
            select c.id, ct.name
            from categories c
            join category_translations ct on c.id = ct.category_id and ct.lang_code = :lang
            order by ct.name
        ")?->bind_values(['lang' => $lang])?->execute()?->fetch_all() ?? [];
        $categories = array_map(fn($r) => (object)$r, $cat_rows);

        $where_category = is_null($category_id) ? '' : ' and p.category_id = :cat_id ';
        $where_search = is_null($search) ? '' : ' and pt.name like :search ';

        $sql = "
            select p.id, pt.name, p.price,
                   (select image_url from product_images where product_id = p.id order by number asc limit 1) as image_url
            from products p
            join product_translations pt on p.id = pt.product_id and pt.lang_code = :lang
            where p.visible = 1 {$where_category} {$where_search}
            order by p.id
        ";
        $stmt = DB_Model::query($sql);
        $bind = ['lang' => $lang];
        if (!is_null($category_id)) $bind['cat_id'] = $category_id;
        if (!is_null($search) && $search !== '') $bind['search'] = "%{$search}%";
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

        $content = View::func(function () use ($categories, $category_id, $current_page_items, $is_admin, $page, $total_pages, $base_url, $cart_ids, $search) {
            $left = Products_View::category_panel($categories, $is_admin, $category_id)->render();
            $grid = Products_View::product_grid($current_page_items, $is_admin, $search, $cart_ids)->render();
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
            select author_name, rating, date, text
            from reviews
            where product_id = :id
            order by date desc
            ")?->bind_values(['id' => $id])?->execute()?->fetch_all() ?: [];
        $reviews = array_map(fn($r) => (object)$r, $reviews_rows);

        $avg_row = DB_Model::query("
            select avg(rating) as avg_rating from reviews where product_id = :id
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

    private static function require_admin(Request $req): ?object {
        $user = $req->additional['user'] ?? null;
        return ($user && $user->privilege === User_Privileges::ADMIN) ? $user : null;
    }

    public static function add_form(Request $req): Response {
        $user = self::require_admin($req);
        if (!$user) return Response::redirect('/');

        $lang = Locale::get_language();
        $cat_rows = DB_Model::query("
            select c.id, ct.name
            from categories c
            join category_translations ct on c.id = ct.category_id and ct.lang_code = :lang
            order by ct.name
            ")?->bind_values(['lang' => $lang])?->execute()?->fetch_all() ?: [];
        $categories = array_map(fn($r) => (object)$r, $cat_rows);

        $comp = Products_View::add_product_form($categories);
        return Response::view(Common_View::layout(
            $comp,
            title: Locale::get('products.add_product_title'),
            page_name: 'products',
            user: $user,
            scripts: [
                Js_Script::from('//code.jquery.com/jquery-3.6.0.min.js'),
                Js_Script::from('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js'),
                Js_Script::from('https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js'),
            ]
        ));
    }

    public static function create(Request $req): Response {
        $user = self::require_admin($req);
        if (!$user) return Response::redirect('/');

        $name_ru = trim($req->form['name_ru'] ?? '');
        $name_en = trim($req->form['name_en'] ?? '');
        $description_ru = $req->form['description_ru'] ?? '';
        $description_en = $req->form['description_en'] ?? '';
        $price = (float)($req->form['price'] ?? 0);
        $category_id = (int)($req->form['category_id'] ?? 0);

        $errors = [];
        if ($name_ru === '') $errors[] = 'Название (RU) обязательно';
        if ($name_en === '') $errors[] = 'Название (EN) обязательно';
        if ($price <= 0) $errors[] = 'Цена должна быть больше нуля';
        if ($category_id <= 0) $errors[] = 'Выберите категорию';

        if (!empty($errors)) {
            $lang = Locale::get_language();
            $cat_rows = DB_Model::query("
                select c.id, ct.name
                from categories c
                join category_translations ct on c.id = ct.category_id and ct.lang_code = :lang
                order by ct.name
                ")?->bind_values(['lang' => $lang])?->execute()?->fetch_all() ?: [];
            $categories = array_map(fn($r) => (object)$r, $cat_rows);
            $error_msg = implode('<br>', $errors);
            $comp = Products_View::add_product_form($categories, $error_msg);
            return Response::view(Common_View::layout(
                $comp,
                title: Locale::get('products.add_product_title'),
                page_name: 'products',
                user: $user,
                scripts: [
                    Js_Script::from('//code.jquery.com/jquery-3.6.0.min.js'),
                    Js_Script::from('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js'),
                    Js_Script::from('https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js'),
                ]
            ));
        }

        DB_Model::query("insert into products (category_id, price, visible) values (:cat, :price, 1)")
        ?->bind_values(['cat' => $category_id, 'price' => $price])?->execute();
        $product_id = DB_Model::$conn->lastInsertId();

        DB_Model::query("insert into product_translations (product_id, lang_code, name, description) values (:id, 'ru', :name, :desc)")
        ?->bind_values(['id' => $product_id, 'name' => $name_ru, 'desc' => $description_ru])?->execute();
        DB_Model::query("insert into product_translations (product_id, lang_code, name, description) values (:id, 'en', :name, :desc)")
        ?->bind_values(['id' => $product_id, 'name' => $name_en, 'desc' => $description_en])?->execute();

        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = 'public/product_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $number = 1;
            foreach ($_FILES['images']['tmp_name'] as $idx => $tmp_name) {
                if ($_FILES['images']['error'][$idx] === UPLOAD_ERR_OK) {
                    $orig_name = basename($_FILES['images']['name'][$idx]);
                    $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
                    $new_name = uniqid('prod_') . '.' . $ext;
                    $dest = $upload_dir . $new_name;
                    if (move_uploaded_file($tmp_name, $dest)) {
                        $image_url = '/' . $dest;
                        DB_Model::query("insert into product_images (product_id, number, image_url) values (:pid, :num, :url)")
                        ?->bind_values(['pid' => $product_id, 'num' => $number, 'url' => $image_url])?->execute();
                        $number++;
                    }
                }
            }
        }

        return Response::redirect('/products/' . $product_id);
    }

    public static function delete(Request $req): Response {
        $user = self::require_admin($req);
        if (is_null($user)) {
            return Response::redirect('/login');
        }

        $id = $req->binds['id'] ?? null;
        if (is_null($id) || !is_numeric($id)) {
            return Response::redirect('/');
        }
        $id = (int)$id;

        $images_url = DB_Model::query('select image_url from product_images where product_id = :id')
            ?->bind_values(['id' => $id])
            ?->execute()
            ?->fetch_all();
        Log::trace(print_r($images_url, true));
        if (!is_null($images_url)) {
            foreach ($images_url as $img_url) {
                $img_url = '.' . $img_url['image_url'];
                if (!str_starts_with($img_url, './public/product_images')) {
                    Log::warning(__METHOD__.": Invalid image placement: $img_url");
                    continue;
                }
                if (!file_exists($img_url)) {
                    Log::warning(__METHOD__.": File no exists: $img_url");
                    continue;
                }
                if (!unlink($img_url)) {
                    Log::warning(__METHOD__.": Unable to delete image: $img_url");
                } else {
                    Log::info(__METHOD__.": Image successfuly deleted: $img_url");
                }
            }
        }

        if (\Config::IS_USING_SQLITE) {
            DB_Model::query('pragma foreign_keys = on')?->execute();
        }
        $res = DB_Model::query('delete from products where id = :id')
            ?->bind_values(['id' => $id])
            ?->execute();
        if (is_null($res)) {
            return Response::redirect('/');
        }

        return Response::redirect('/products');
    }
}


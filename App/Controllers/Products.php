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
use App\Core\Model\AR_Reflect;
use App\Core\Model\DB_Type;
use App\Core\View\Js_Script;
use App\Models\Dto\Category;
use App\Models\Dto\Product;
use App\Models\Dto\Product_Showcase;
use App\Models\Dto\User_Privileges;

final class Products {
    private function __construct() {}

    public static function index(Request $req): Response {
        $lang = Locale::get_language();
        $category_id = isset($req->url->query['category']) ? (int)$req->url->query['category'] : null;
        $page = max(1, (int)($req->url->query['page'] ?? 1));
        $per_page = 8;
        $user = $req->additional['user'] ?? null;
        $is_admin = (isset($user) && $user->privilege === User_Privileges::ADMIN);

        $search = $req->form['search'] ?? null;

        $cat_rows = DB_Model::query(Category::select_all())
            ->bind_values(['lang' => $lang])
            ->fetch_all()
            ->or_else([]);
        $categories = AR_Reflect::construct_many(Category::class, $cat_rows);

        $bind = ['lang' => $lang];
        if (!is_null($category_id)) $bind['cat_id'] = $category_id;
        if (!is_null($search) && $search !== '') $bind['search'] = "%{$search}%";

        $all_rows = DB_Model::query(Product_Showcase::select_showcase($category_id, $search))
            ->bind_values($bind)
            ->fetch_all()
            ->or_else([]);
        $all_products = AR_Reflect::construct_many(Product_Showcase::class, $all_rows);

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

        $res = DB_Model::query(Product::select_id())
            ->bind_values(['lang' => $lang, 'id' => $id])
            ->fetch();

        if (!$res->ok) {
            return Response::redirect('/products');
        }
        $row = $res->val;

        $images = DB_Model::query("
            select image_url from product_images where product_id = :id order by number asc
            ")->bind_values(['id' => $id])->fetch_all()->or_else([]);

        $reviews_rows = DB_Model::query("
            select author_name, rating, date, text
            from reviews
            where product_id = :id
            order by date desc
            ")?->bind_values(['id' => $id])->fetch_all()->or_else([]);
        $reviews = array_map(fn($r) => (object)$r, $reviews_rows);

        $avg_row = DB_Model::query("
            select avg(rating) as avg_rating from reviews where product_id = :id
            ")->bind_values(['id' => $id])->fetch();
        $avg_rating = $avg_row->ok ? round((float)($avg_row->val['avg_rating'] ?? 0), 1) : null;

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

        $resp = Response::redirect("/products/{$id}#reviews");

        if ($rating < 1 || $rating > 5 || empty($text)) {
            return $resp;
        }

        DB_Model::begin_transaction();
        $res = DB_Model::query("insert into reviews (product_id, author_name, text, rating) values (:pid, :author, :text, :rating)")
            ->bind_values([
                'pid' => $id,
                'author' => $user->login,
                'text' => $text,
                'rating' => $rating,
            ])->execute();
        if (!$res->ok) {
            DB_Model::roll_back();
            return $resp;
        }
        DB_Model::commit();

        return $resp;
    }

    public static function add_form(Request $req): Response {
        $user = $req->additional['user'];
        if (!$user) return Response::redirect('/');

        $lang = Locale::get_language();
        $cat_rows = DB_Model::query("
            select c.id, ct.name
            from categories c
            join category_translations ct on c.id = ct.category_id and ct.lang_code = :lang
            order by ct.name
            ")->bind_values(['lang' => $lang])->fetch_all()->or_else([]);
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
        $user = $req->additional['user'];
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
                ")->bind_values(['lang' => $lang])->fetch_all()->or_else([]);
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

        $err_resp = Response::redirect('/products/add');
        DB_Model::begin_transaction();
        if (!DB_Model::query("insert into products (category_id, price) values (:cat, :price)")
            ->bind_values(['cat' => $category_id, 'price' => $price])->execute()->ok) {
            DB_Model::roll_back();
            return $err_resp;
        }
        $product_id = DB_Model::$conn->lastInsertId();

        if (!DB_Model::query("insert into product_translations (product_id, lang_code, name, description) values (:id, 'ru', :name, :desc)")
            ->bind_values(['id' => $product_id, 'name' => $name_ru, 'desc' => $description_ru])->execute()->ok) {
            DB_Model::roll_back();
            return $err_resp;
        }
        if (!DB_Model::query("insert into product_translations (product_id, lang_code, name, description) values (:id, 'en', :name, :desc)")
            ->bind_values(['id' => $product_id, 'name' => $name_en, 'desc' => $description_en])->execute()->ok) {
            DB_Model::roll_back();
            return $err_resp;
        }
        DB_Model::commit();

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
                            ->bind_values(['pid' => $product_id, 'num' => $number, 'url' => $image_url])->execute();
                        $number++;
                    }
                }
            }
        }

        return Response::redirect('/products/' . $product_id);
    }

    public static function delete(Request $req): Response {
        $user = $req->additional['user'];
        if (is_null($user)) {
            return Response::redirect('/login');
        }

        $id = $req->binds['id'] ?? null;
        if (is_null($id) || !is_numeric($id)) {
            return Response::redirect('/');
        }
        $id = (int)$id;

        $images_url = DB_Model::query('select image_url from product_images where product_id = :id')
            ->bind_values(['id' => $id])
            ->fetch_all();
        if ($images_url->ok) {
            foreach ($images_url->val as $img_url) {
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

        DB_Model::begin_transaction();
        if (DB_Model::$current_db === DB_Type::SQLITE) {
            DB_Model::query('pragma foreign_keys = on')->execute();
        }
        $res = DB_Model::query('delete from products where id = :id')
            ->bind_values(['id' => $id])
            ->execute();
        if (is_null($res)) {
            DB_Model::roll_back();
            return Response::redirect('/');
        }
        DB_Model::commit();

        return Response::redirect('/products');
    }
}


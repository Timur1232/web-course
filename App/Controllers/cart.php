<?php
namespace App\Controllers;

use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Locale;
use App\Core\View\View;
use App\Core\Model\DB_Model;
use App\Views\Common_View;

final class Cart {
    private function __construct() {}

    public static function index(Request $req): Response {
        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $total = 0.0;
        $lang = Locale::get_language();

        if (!empty($cart)) {
            $ids = array_keys($cart);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $rows = DB_Model::query("
                select p.id, pt.name, p.price
                from products p
                join product_translations pt on p.id = pt.product_id and pt.lang_code = ?
                where p.id in ($placeholders) and p.visible = 1
                ")?->bind_values(array_merge([$lang], $ids))?->execute()?->fetch_all() ?: [];

            foreach ($rows as $row) {
                $id = $row['id'];
                $qty = $cart[$id];
                $price = (float)$row['price'];
                $items[] = [
                    'product' => (object)['id' => $id, 'name' => $row['name'], 'price' => $price],
                    'quantity' => $qty
                ];
                $total += $price * $qty;
            }
        }

        $content = \App\Views\Cart::cart_page($items, $total);
        $user = $req->additional['user'] ?? null;
        return Response::view(Common_View::layout(
            $content,
            title: Locale::get('cart.title'),
            page_name: 'cart',
            user: $user
        ));
    }

    public static function add(Request $req): Response {
        $product_id = (int)($req->form['product_id'] ?? 0);
        if ($product_id <= 0) {
            return Response::view(View::func(fn() => ''));
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = 1;
        }

        $count = count($_SESSION['cart']);
        $oob = '<span id="cart-count" class="cart-count" hx-swap-oob="true">' . ($count > 0 ? $count : '') . '</span>';
        $button_html = '<button class="product-card-added" disabled>' . htmlspecialchars(Locale::get('products.added')) . '</button>';

        return Response::view(View::func(fn() => $button_html . $oob));
    }

    public static function update_quantity(Request $req): Response {
        $product_id = (int)($req->form['product_id'] ?? 0);
        $delta = (int)($req->form['delta'] ?? 0);

        if ($product_id <= 0) {
            return Response::view(View::func(fn() => ''));
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $delta;
            if ($_SESSION['cart'][$product_id] <= 0) {
                unset($_SESSION['cart'][$product_id]);
            }
        }

        $items = self::get_cart_items();
        $total = self::get_cart_total($items);
        $html = \App\Views\Cart::render_items($items);
        $oob = '<span id="cart-count" class="cart-count" hx-swap-oob="true">' . (count($_SESSION['cart']) > 0 ? count($_SESSION['cart']) : '') . '</span>';
        $total_html = '<div class="cart-total" id="cart-total" hx-swap-oob="true">' . htmlspecialchars(Locale::get('cart.total')) . ': ' . number_format($total, 2, '.', ' ') . ' €</div>';
        return Response::view(View::func(fn() => $html . $oob . $total_html));
    }

    public static function remove(Request $req): Response {
        $product_id = (int)($req->form['product_id'] ?? 0);
        if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }

        $items = self::get_cart_items();
        $total = self::get_cart_total($items);
        $html = \App\Views\Cart::render_items($items);
        $oob = '<span id="cart-count" class="cart-count" hx-swap-oob="true">' . (count($_SESSION['cart']) > 0 ? count($_SESSION['cart']) : '') . '</span>';
        $total_html = '<div class="cart-total" id="cart-total" hx-swap-oob="true">' . htmlspecialchars(Locale::get('cart.total')) . ': ' . number_format($total, 2, '.', ' ') . ' €</div>';
        return Response::view(View::func(fn() => $html . $oob . $total_html));
    }

    public static function checkout(Request $req): Response {
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            return Response::redirect('/cart');
        }

        $customer_name = $req->form['customer_name'] ?? '';
        $phone = $req->form['phone'] ?? '';
        $email = $req->form['email'] ?? '';
        $payment_method = $req->form['payment_method'] ?? '';
        $delivery_method = $req->form['delivery_method'] ?? 'pickup';
        $delivery_address = ($delivery_method === 'delivery') ? ($req->form['delivery_address'] ?? '') : '';

        if (empty($customer_name) || empty($phone) || empty($email)) {
            $items = self::get_cart_items();
            $total = self::get_cart_total($items);
            $content = \App\Views\Cart::cart_page($items, $total, Locale::get('cart.error_missing_fields'));
            $user = $req->additional['user'] ?? null;
            return Response::view(Common_View::layout(
                $content,
                title: Locale::get('cart.title'),
                page_name: 'cart',
                user: $user
            ));
        }

        $items = self::get_cart_items();
        $total = self::get_cart_total($items);

        $order_sql = "insert into orders (customer_name, phone, email, payment_method, delivery_method, delivery_address, status, total) values (:name, :phone, :email, :payment, :delivery, :address, 'new', :total)";
        $order_stmt = DB_Model::query($order_sql)?->bind_values([
            'name' => $customer_name,
            'phone' => $phone,
            'email' => $email,
            'payment' => $payment_method,
            'delivery' => $delivery_method,
            'address' => $delivery_address,
            'total' => $total
        ])?->execute();

        if (!$order_stmt) {
            $content = \App\Views\Cart::cart_page($items, $total, 'Ошибка сервера');
            $user = $req->additional['user'] ?? null;
            return Response::view(Common_View::layout($content, title: Locale::get('cart.title'), page_name: 'cart', user: $user));
        }

        $order_id = DB_Model::$conn->lastInsertId();

        foreach ($items as $item) {
            $pid = $item['product']->id;
            $qty = $item['quantity'];
            $price = $item['product']->price;
            DB_Model::query("insert into ordered_products (order_id, product_id, count, price) values (:oid, :pid, :cnt, :price)")
            ?->bind_values(['oid' => $order_id, 'pid' => $pid, 'cnt' => $qty, 'price' => $price])
            ?->execute();
        }

        $_SESSION['cart'] = [];

        $content = View::func(fn() => '<p class="order-success">' . htmlspecialchars(Locale::get('cart.order_success')) . '</p>');
        $user = $req->additional['user'] ?? null;
        return Response::view(Common_View::layout(
            $content,
            title: Locale::get('cart.order_success'),
            page_name: 'cart',
            user: $user
        ));
    }

    private static function get_cart_items(): array {
        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        if (!empty($cart)) {
            $lang = Locale::get_language();
            $ids = array_keys($cart);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $rows = DB_Model::query("
                select p.id, pt.name, p.price
                from products p
                join product_translations pt on p.id = pt.product_id and pt.lang_code = ?
                where p.id in ($placeholders) and p.visible = 1
                ")?->bind_values(array_merge([$lang], $ids))?->execute()?->fetch_all() ?: [];

            foreach ($rows as $row) {
                $id = $row['id'];
                $qty = $cart[$id];
                $items[] = [
                    'product' => (object)['id' => $id, 'name' => $row['name'], 'price' => (float)$row['price']],
                    'quantity' => $qty
                ];
            }
        }
        return $items;
    }

    private static function get_cart_total(array $items): float {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['product']->price * $item['quantity'];
        }
        return $total;
    }
}

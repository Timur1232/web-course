<?php namespace App\Views;

use App\Core\View\Component;
use App\Core\View\View;
use App\Core\Locale;

final class Cart {
    public static function cart_page(array $items, float $total, ?string $error = null): Component {
        return View::func(function () use ($items, $total, $error) {
            $__ = fn($key) => Locale::get("cart.{$key}");
            $html = '<div class="cart-page">';
            $html .= '<h2>' . $__('title') . '</h2>';

            $html .= \App\Views\Cart::render_items($items);
            $html .= '<span id="cart-message"></span>';
            $html .= '<div class="cart-total" id="cart-total">' . $__('total') . ': ' . number_format($total, 2, '.', ' ') . ' ₸</div>';

            if (!empty($items)) {
                $html .= '<hr>';
                $html .= self::checkout_form($error)->render();
            }

            $html .= '</div>';
            return $html;
        });
    }

    public static function render_items(array $items): string {
        $__ = fn($key) => Locale::get("cart.{$key}");
        if (empty($items)) {
            return '<div class="cart-items" id="cart-items"><p class="empty-cart">' . $__('empty_cart') . '</p></div>';
        }
        $rows = '';
        foreach ($items as $item) {
            $p = $item['product'];
            $qty = $item['quantity'];
            $id = $p->id;
            $name = $p->name;
            $price = number_format($p->price, 2, '.', ' ');
            $subtotal = number_format($p->price * $qty, 2, '.', ' ');
            $rows .= <<<HTML
            <div class="cart-item" id="cart-item-{$id}">
                <span class="cart-item-name">{$name}</span>
                <span class="cart-item-price">{$price} €</span>
                <span class="cart-item-qty">
                    <button class="qty-btn" hx-post="/cart/update" hx-vals='{"product_id":{$id},"delta":-1}' hx-target="#cart-items" hx-swap="outerHTML">{$__('decrease')}</button>
                    <span class="qty-val">{$qty}</span>
                    <button class="qty-btn" hx-post="/cart/update" hx-vals='{"product_id":{$id},"delta":1}' hx-target="#cart-items" hx-swap="outerHTML">{$__('increase')}</button>
                </span>
                <span class="cart-item-subtotal">{$subtotal} ₸</span>
                <button class="remove-btn" hx-post="/cart/remove" hx-vals='{"product_id":{$id}}' hx-target="#cart-items" hx-swap="outerHTML">{$__('remove')}</button>
            </div>
            HTML;
        }
        return '<div class="cart-items" id="cart-items">' . $rows . '</div>';
    }

    public static function checkout_form(?string $error = null): Component {
        return View::func(function () use ($error) {
            $__ = fn($key) => Locale::get("cart.{$key}");
            $error_html = $error ? '<div class="form-error">' . $error . '</div>' : '';
            return <<<HTML
            <form class="form" method="post" action="/cart/checkout" id="checkout-form">
                <h3 class="form-title">{$__('checkout_title')}</h3>
                {$error_html}
                <label>{$__('customer_name')}</label>
                <input type="text" name="customer_name" required>
                <label>{$__('phone')}</label>
                <input type="tel" name="phone" required>
                <label>{$__('email')}</label>
                <input type="email" name="email" required>

                <label>{$__('payment_method')}</label>
                <select name="payment_method">
                    <option value="card">Карта</option>
                    <option value="cash">Наличные</option>
                </select>

                <label>{$__('delivery_method')}</label>
                <select name="delivery_method" id="delivery-method" onchange="document.getElementById('address-field').style.display = this.value === 'delivery' ? 'block' : 'none'">
                    <option value="pickup">{$__('pickup')}</option>
                    <option value="delivery">{$__('delivery')}</option>
                </select>

                <div id="address-field" style="display:none;">
                    <label>{$__('address')}</label>
                    <input type="text" name="delivery_address">
                </div>

                <button type="submit" class="form-submit">{$__('submit_order')}</button>
            </form>
            HTML;
        });
    }

    private function __construct() {}
}

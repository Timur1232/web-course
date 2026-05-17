<?php namespace App\Views;

use App\Core\View\Component;
use App\Core\View\View;
use App\Core\Locale;

final class Products {
    public static function category_panel(array $categories, ?int $active_id = null): Component {
        return View::func(function () use ($categories, $active_id) {
            $__ = fn($key, $replace = []) => Locale::get("products.{$key}", $replace);
            $html = '<div class="categories-panel">';
            $html .= '<h3>' . htmlspecialchars($__('categories')) . '</h3>';
            $html .= '<ul>';

            $active_class = is_null($active_id) ? ' class="active"' : '';
            $html .= '<li><a href="/products"' . $active_class . '>' . htmlspecialchars($__('all_categories')) . '</a></li>';

            foreach ($categories as $cat) {
                $active = ($active_id == $cat->id) ? ' class="active"' : '';
                $html .= '<li><a href="/products?category=' . $cat->id . '"' . $active . '>'
                       . htmlspecialchars($cat->name) . '</a></li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
            return $html;
        });
    }

    public static function product_card(object $product, array $cart_ids = []): Component {
        return View::func(function () use ($product, $cart_ids) {
            $__ = fn($key) => Locale::get("products.{$key}");
            $img = htmlspecialchars($product->image_url ?? '/public/media/placeholder.png');
            $name = htmlspecialchars($product->name);
            $price = number_format($product->price, 2, '.', ' ');
            $id = (int)$product->id;
            $in_cart = in_array($id, $cart_ids);

            $button = $in_cart
                ? '<button class="product-card-added" disabled>' . $__('added') . '</button>'
                : "<button class=\"product-card-add\" hx-post=\"/cart/add\" hx-vals='{\"product_id\":{$id}}' hx-swap=\"outerHTML\">{$__('add_to_cart')}</button>";

            return <<<HTML
                <div class="product-card">
                <a href="/products/{$id}" class="product-card-link">
                <div class="product-card-image">
                <img src="{$img}" alt="{$name}">
                </div>
                <div class="product-card-name">{$name}</div>
                </a>
                <div class="product-card-price">{$price} €</div>
                {$button}
                </div>
                HTML;
        });
    }

    public static function product_grid(array $products, array $cart_ids = []): Component {
        return View::func(function () use ($products, $cart_ids) {
            if (empty($products)) {
                $__ = fn($key) => Locale::get("products.{$key}");
                return '<p class="no-products">' . htmlspecialchars($__('no_products')) . '</p>';
            }
            $cards = array_map(fn($p) => self::product_card($p, $cart_ids)->render(), $products);
            return '<div class="product-grid">' . implode('', $cards) . '</div>';
        });
    }

    public static function pagination(int $current_page, int $total_pages, string $base_url): Component {
        return View::func(function () use ($current_page, $total_pages, $base_url) {
            if ($total_pages <= 1) return '';
            $html = '<div class="pagination">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $active = $i === $current_page ? ' class="active"' : '';
                $sep = strpos($base_url, '?') === false ? '?' : '&';
                $html .= '<a href="' . $base_url . $sep . 'page=' . $i . '"' . $active . '>' . $i . '</a>';
            }
            $html .= '</div>';
            return $html;
        });
    }

    public static function product_detail(object $product, array $cart_ids = []): Component {
        return View::func(function () use ($product, $cart_ids) {
            $__ = fn($key) => Locale::get("products.{$key}");
            $img_first = htmlspecialchars($product->images[0]->image_url ?? '/public/media/placeholder.png');
            $name = htmlspecialchars($product->name);
            $price = number_format($product->price, 2, '.', ' ');
            $desc = nl2br(htmlspecialchars($product->description ?? ''));
            $id = (int)$product->id;
            $in_cart = in_array($id, $cart_ids);

            $thumbs = '';
            foreach ($product->images as $img) {
                $url = htmlspecialchars($img->image_url);
                $thumbs .= "<img src=\"{$url}\" class=\"product-thumb\" onclick=\"document.getElementById('main-image').src='{$url}'\">";
            }

            $button = $in_cart
                ? '<button class="product-card-added" disabled>' . $__('added') . '</button>'
                : "<button class=\"product-card-add\" hx-post=\"/cart/add\" hx-vals='{\"product_id\":{$id}}' hx-swap=\"outerHTML\">{$__('add_to_cart')}</button>";

            return <<<HTML
                <div class="product-detail">
                    <a href="/products" class="back-link">{$__('back_to_catalog')}</a>
                    <div class="product-detail-main">
                        <div class="product-detail-gallery">
                            <img id="main-image" src="{$img_first}" alt="{$name}" class="main-image">
                            <div class="product-thumbnails">{$thumbs}</div>
                        </div>
                        <div class="product-detail-info">
                            <h2>{$name}</h2>
                            <p class="product-detail-price">{$price} €</p>
                            <div class="product-detail-desc">
                                <h3>{$__('description')}</h3>
                                <p>{$desc}</p>
                            </div>
                            {$button}
                        </div>
                    </div>
                </div>
                HTML;
        });
    }

    private function __construct() {}
}

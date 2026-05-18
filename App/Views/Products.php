<?php namespace App\Views;

use App\Core\View\Component;
use App\Core\View\View;
use App\Core\Locale;
use App\Models\User;

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

    public static function product_detail(object $product, array $cart_ids = [], ?User $user = null, array $reviews = [], ?float $avg_rating = null): Component {
        return View::func(function () use ($product, $cart_ids, $user, $reviews, $avg_rating) {
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

            $html = <<<HTML
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
            $html .= self::reviews_section($product->id, $reviews, $avg_rating, $user)->render();
            return $html;
        });
    }

    public static function reviews_section(int $product_id, array $reviews, ?float $avg, ?User $user = null): Component {
        return View::func(function () use ($product_id, $reviews, $avg, $user) {
            $__ = fn($key) => Locale::get("reviews.{$key}");
            $html = '<div class="reviews-section" id="reviews">';
            $html .= '<h3>' . htmlspecialchars($__('reviews_title')) . '</h3>';

            if (is_null($avg)) {
                $html .= '<p class="no-reviews">' . htmlspecialchars($__('no_reviews')) . '</p>';
            } else {
                $html .= '<p class="average-rating">' . htmlspecialchars($__('average_rating')) . ': ' . number_format($avg, 1) . ' ' . htmlspecialchars($__('out_of_5')) . '</p>';
            }

            if ($user) {
                $html .= self::review_form($product_id)->render();
            } else {
                $redirect = urlencode("/products/{$product_id}#reviews");
                $html .= '<a href="/login?redirect=' . $redirect . '" class="login-to-review">' . htmlspecialchars($__('login_to_review')) . '</a>';
            }

            foreach ($reviews as $r) {
                $html .= self::review_item($r)->render();
            }


            $html .= '</div>';
            return $html;
        });
    }

    public static function review_item(object $review): Component {
        return View::func(function () use ($review) {
            $author = htmlspecialchars($review->author_name);
            $rating = (int)$review->rating;
            $date = htmlspecialchars($review->date);
            $text = nl2br(htmlspecialchars($review->text));
            return <<<HTML
                <div class="review-item">
                    <div class="review-header">
                        <span class="review-author">{$author}</span>
                        <span class="review-rating">{$rating}/5</span>
                        <span class="review-date">{$date}</span>
                    </div>
                    <div class="review-text">{$text}</div>
                </div>
                HTML;
        });
    }

    public static function review_form(int $product_id): Component {
        return View::func(function () use ($product_id) {
            $__ = fn($key) => Locale::get("reviews.{$key}");
            return <<<HTML
                <div class="review-form">
                    <h4>{$__('leave_review')}</h4>
                    <form method="post" action="/products/{$product_id}/review">
                        <label>{$__('rating')}</label>
                        <select name="rating" required>
                            <option value="5">5</option>
                            <option value="4">4</option>
                            <option value="3">3</option>
                            <option value="2">2</option>
                            <option value="1">1</option>
                        </select>
                        <label>{$__('your_review')}</label>
                        <textarea name="text" rows="4" required></textarea>
                        <button type="submit" class="review-submit">{$__('submit_review')}</button>
                    </form>
                </div>
                HTML;
        });
    }

    private function __construct() {}
}

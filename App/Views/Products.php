<?php namespace App\Views;

use App\Core\View\Component;
use App\Core\View\View;
use App\Core\Locale;
use App\Core\View\Component_Func;
use App\Models\User;
use App\Models\User_Privileges;

final class Products {
    public static function category_panel(array $categories, bool $is_admin, ?int $active_id = null): Component {
        return View::func(function () use ($categories, $is_admin, $active_id) {
            $__ = fn($key, $replace = []) => Locale::get("products.{$key}", $replace);
            $html = '';
            if ($is_admin) {
                $html .= '<a href="/products/add_category" class="admin-add-btn" style="margin-bottom:20px;">'
                    . htmlspecialchars($__('add_category')) . '</a>';
            }
            $html .= '<div class="categories-panel">';
            $html .= '<h3>' . htmlspecialchars($__('categories')) . '</h3>';
            $html .= '<ul>';
            $active_class = is_null($active_id) ? ' class="active"' : '';
            $html .= '<li><a class="categories-panel-link" href="/products"' . $active_class . '>' . htmlspecialchars($__('all_categories')) . '</a></li>';

            foreach ($categories as $cat) {
                $active = ($active_id == $cat->id) ? ' class="active"' : '';
                $html .= $is_admin ? '<li class="categories-panel-link-with-controls">' : '<li>';
                $html .= '<a class="categories-panel-link" href="/products?category=' . $cat->id . '"' . $active . '>'
                    . htmlspecialchars($cat->name) . '</a>';
                if ($is_admin) {
                    $confirm = htmlspecialchars(Locale::get('category.delete_confirm'));
                    $html .= <<<HTML
                        <a href="/products/edit_category/{$cat->id}" title="Редактировать">✎</a>
                        <a href="/products/delete_category/{$cat->id}" onclick="return confirm('{$confirm}')" title="Удалить">✕</a>
                    HTML;
                }
                $html .= '</li>';
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
                <div class="product-card-price">{$price} ₸</div>
                {$button}
                </div>
                HTML;
        });
    }

    public static function product_grid(array $products, bool $is_admin, ?string $search, array $cart_ids = []): Component {
        return View::func(function () use ($products, $is_admin, $search, $cart_ids) {
            if (empty($products)) {
                return '<p class="no-products">' . htmlspecialchars(Locale::get('products.no_products')) . '</p>';
            }
            $cards = array_map(fn($p) => self::product_card($p, $cart_ids)->render(), $products);

            $html = '';
            if ($is_admin) {
                $html .= '<a href="/products/add" class="admin-add-btn" style="margin-bottom:20px;">' . htmlspecialchars(Locale::get('products.add_product')) . '</a>';
            }
            if (!is_null($search) && $search !== '') {
                $html .= '<p>' . Locale::get('products.search_msg', ['query' => $search]) . '</p>';
            }
            $html .= '<div class="product-grid">';
            $html .= implode('', $cards) . '</div>';
            return $html;
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
            $desc = $product->description ?? '';
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

            $admin_controls = (is_null($user) || $user->privilege !== User_Privileges::ADMIN) ? '' : <<<HTML
                    <br/>
                    <form action="/products/delete/{$product->id}" method="post"
                        style="display:inline;"
                        onsubmit="return confirm('{$__('delete_confirm')}')">
                        <button type="submit" class="admin-delete-btn">
                            {$__('delete_product')}
                        </button>
                    </form>
                    <hr/>
                HTML;

            $html = <<<HTML
                <div class="product-detail">
                    <a href="/products" class="back-link">{$__('back_to_catalog')}</a>
                    {$admin_controls}
                    <div class="product-detail-main">
                        <div class="product-detail-gallery">
                            <img id="main-image" src="{$img_first}" alt="{$name}" class="main-image">
                            <div class="product-thumbnails">{$thumbs}</div>
                        </div>
                        <div class="product-detail-info">
                            <h2>{$name}</h2>
                            <p class="product-detail-price">{$price} ₸</p>
                            <div class="product-detail-desc">
                                <h3>{$__('description')}</h3>
                                {$desc}
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
                    <form class="form" method="post" action="/products/{$product_id}/review">
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
                        <button type="submit" class="form-submit">{$__('submit_review')}</button>
                    </form>
                </div>
                HTML;
        });
    }

    public static function edit_category_form(string $title, string $action, ?string $error = null): Component_Func {
        return View::func(function () use ($title, $action, $error): string {
            $__ = fn($key) => Locale::get("category.{$key}");
            $error_tag = is_null($error) ? '' : "<span class=\"form-error\">{$error}</span>";

            return <<<HTML
                <form class="form" method="post" action="{$action}">
                    <h2>{$title}</h2>
                    {$error_tag}
                    <label>{$__('edit_name_ru')}</label>
                    <input type="text" name="name_ru" required />

                    <label>{$__('edit_name_en')}</label>
                    <input type="text" name="name_en" required />

                    <button type="submit" class="form-submit">{$__('submit')}</button>
                </form>
                HTML;
        });
    }

    public static function add_product_form(array $categories, ?string $error = null): Component {
        return View::func(function () use ($categories, $error) {
            $__ = fn($key) => Locale::get("products.{$key}");
            $cat_options = '';
            foreach ($categories as $cat) {
                $cat_options .= '<option value="' . $cat->id . '">' . htmlspecialchars($cat->name) . '</option>';
            }
            $error_html = $error ? '<div class="form-error">' . htmlspecialchars($error) . '</div>' : '';
            return <<<HTML
                <div class="form">
                    <h2 class="form-title">{$__('add_product_title')}</h2>
                    {$error_html}
                    <form method="post" action="/products/add" enctype="multipart/form-data">
                        <label>{$__('field_name_ru')}</label>
                        <input type="text" name="name_ru" required>
                        <label>{$__('field_name_en')}</label>
                        <input type="text" name="name_en" required>
                        <label>{$__('field_description_ru')}</label>
                        <textarea id="editor-ru" name="description_ru"></textarea>
                        <label>{$__('field_description_en')}</label>
                        <textarea id="editor-en" name="description_en"></textarea>
                        <label>{$__('field_price')}</label>
                        <input type="number" step="0.01" name="price" required>
                        <label>{$__('field_category')}</label>
                        <select name="category_id" required>
                            <option value="">{$__('select_category')}</option>
                            {$cat_options}
                        </select>
                        <label>{$__('field_images')}</label>
                        <input type="file" name="images[]" multiple accept="image/*">
                        <button type="submit" class="form-submit">{$__('submit')}</button>
                    </form>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script>
                <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" />
                <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.css" rel="stylesheet"/>
                <script>
                $(document).ready(function() {
                    $('#editor-ru').summernote({ height: 200, lang: 'ru-RU' });
                    $('#editor-en').summernote({ height: 200, lang: 'en-US' });
                });
                </script>
                HTML;
        });
    }

    private function __construct() {}
}

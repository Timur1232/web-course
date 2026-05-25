<?php namespace App\Views;
use App\Core\View\Component;
use App\Core\View\View;
use App\Models\Dto\User;
use App\Models\Dto\User_Privileges;

final class Common_View {
    /**
     * @param Js_Script[] $scripts
     * @param array<string,mixed> $data
     */
    public static function template_with_layout(
        string $template_page,
        string $title,
        ?User $user = null,
        array $data = [],
        ?string $page_name = null,
        array $scripts = []
    ): Component {
        $comp = View::template($template_page, data: $data);
        return self::layout($comp, title: $title, page_name: $page_name ?? $template_page, user: $user, scripts: $scripts);
    }

    /**
     * @param Js_Script[] $scripts
     */
    public static function layout(
        Component $comp,
        string $title,
        string $page_name,
        ?User $user,
        array $scripts = [],
    ): Component {
        return View::func(function () use ($comp, $title, $scripts, $page_name, $user) {
            $__ = function(string $key, array $replace = []) {
                return \App\Core\Locale::get("layout.{$key}", $replace);
            };
            ob_start();
            ?>
            <!DOCTYPE html>
                <html lang="ru-RU">
                <head>
                    <title><?= htmlspecialchars($title) ?></title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">

                    <link rel="stylesheet" type="text/css" href="/public/styles/style.css">
                    <link rel="icon" href="/public/media/favicon.ico">

                    <script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.8/dist/htmx.min.js" integrity="sha384-/TgkGk7p307TH7EXJDuUlgG3Ce1UVolAOFopFekQkkXihi5u/6OCvVKyz1W+idaz" crossorigin="anonymous"></script>
                    <script type="text/javascript" src="/public/js/jquery.js"></script>
                    <script>
                        htmx.on("htmx:beforeSwap", function(evt) {
                            evt.detail.shouldSwap = true;
                        });
                    </script>
                    <?php foreach ($scripts as $script): ?>
                        <?= $script->render_script() ?>
                    <?php endforeach ?>
                </head>
                <body>
                    <header>
                        <div class="header-inner">
                            <div class="header-top">
                                <a href="/" class="logo"><?= htmlspecialchars($__('logo')) ?></a>
                                <form action="/products/search" method="get">
                                    <div class="search-bar">
                                        <input type="text" name="search" placeholder="<?= htmlspecialchars($__('search_placeholder')) ?>">
                                        <button type="submit"><?= htmlspecialchars($__('search_button')) ?></button>
                                    </div>
                                </form>
                                <a href="/cart" class="cart-button"><?= htmlspecialchars($__('cart')) ?></a>
                                <span id="cart-count" class="cart-count">
                                    <?= count($_SESSION['cart'] ?? []) ?: '' ?>
                                </span>
                                <div class="lang-select">
                                    <select id="lang_select" onchange="document.cookie='lang='+this.value+';path=/';location.reload();">
                                        <option value="ru" <?= ($_COOKIE['lang'] ?? 'ru') === 'ru' ? 'selected' : '' ?>>RU</option>
                                        <option value="en" <?= ($_COOKIE['lang'] ?? 'ru') === 'en' ? 'selected' : '' ?>>EN</option>
                                    </select>
                                    <label style="font-size:14px;" for="lang_select">🌐</label>
                                </div>
                                <div class="user-actions" id="user-actions">
                                    <?php if (is_null($user)): ?>
                                    <a href="/login" class="login-button"><?= htmlspecialchars($__('login')) ?></a>
                                    <?php else: ?>
                                    <span class="user-login"><?= htmlspecialchars(strlen($user->login) > 15 ? substr($user->login, 0, 15).'...' : $user->login) ?></span>
                                    <a href="#" class="logout-button"
                                        onclick="document.cookie='jwt_token=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';location.reload();"
                                    >
                                        <?= htmlspecialchars($__('logout')) ?>
                                    </a>
                                    <?php endif ?>
                                </div>
                            </div>
                            <nav class="main-nav">
                                <ul>
                                    <li><a href="/products" class="<?= $page_name === 'products' ? 'active' : '' ?>"><?= htmlspecialchars($__('menu.products')) ?></a></li>
                                    <li><a href="/about" class="<?= $page_name === 'about' ? 'active' : '' ?>"><?= htmlspecialchars($__('menu.about')) ?></a></li>
                                    <li><a href="/news" class="<?= $page_name === 'news' ? 'active' : '' ?>"><?= htmlspecialchars($__('menu.news')) ?></a></li>
                                    <li><a href="/promotions" class="<?= $page_name === 'promotions' ? 'active' : '' ?>"><?= htmlspecialchars($__('menu.promotions')) ?></a></li>
                                    <li><a href="/feedback" class="<?= $page_name === 'feedback' ? 'active' : '' ?>"><?= htmlspecialchars($__('menu.feedback')) ?></a></li>
                                    <li><a href="/site_scheme" class="<?= $page_name === 'site_scheme' ? 'active' : '' ?>"><?= htmlspecialchars($__('menu.site_scheme')) ?></a></li>
                                    <li><a href="/map" class="<?= $page_name === 'map' ? 'active' : '' ?>"><?= htmlspecialchars($__('menu.map')) ?></a></li>
                                    <?php if ($user && $user->privilege === User_Privileges::ADMIN): ?>
                                    <li><a href="/admin" class="<?= $page_name === 'admin' ? 'active' : '' ?>"><?= htmlspecialchars($__('menu.admin')) ?></a></li>
                                    <?php endif ?>
                                </ul>
                            </nav>
                        </div>
                    </header>
                    <div class="page-wrapper">
                        <main>
                            <?= $comp->render() ?>
                        </main>
                    </div>
                    <footer>
                        <div class="footer-inner">
                            <?= htmlspecialchars($__('footer', ['year' => date('Y')])) ?>
                        </div>
                    </footer>
                </body>
                </html>
            <?php
            return ob_get_clean();
        });
    }

    private function __construct() {}
}

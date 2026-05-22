<?php namespace App\Controllers;

use \Config;
use App\Core\Context\Request;
use App\Core\Context\Response;
use App\Core\Locale;
use App\Core\View\View;
use App\Views\Common_View;

final class Map {
    private function __construct() {}

    public static function index(Request $req): Response {
        $user = $req->additional['user'] ?? null;

        $content = View::func(function () {
            $api_key = Config::YANDEX_MAP_SECRET_API_KEY;
            $shop_coords = '[44.59500906749677,33.47573134838073]';
            $shop_name = 'Мир Музыки';
            return <<<HTML
            <div id="map" style="width:100%; height:500px;"></div>
            <script src="https://api-maps.yandex.ru/2.1/?apikey={$api_key}&lang=ru_RU" type="text/javascript"></script>
            <script type="text/javascript">
                ymaps.ready(function () {
                    const shopCoords = {$shop_coords};
                    const shopName = '{$shop_name}';

                    const myMap = new ymaps.Map('map', {
                        center: shopCoords,
                        zoom: 17,
                        controls: ['zoomControl', 'fullscreenControl']
                    });

                    const shopPlacemark = new ymaps.Placemark(shopCoords, {
                        hintContent: shopName,
                        balloonContent: '<strong>' + shopName + '</strong><br/>Магазин музыкальных инструментов'
                    }, {
                        preset: 'islands#redIcon'
                    });
                    myMap.geoObjects.add(shopPlacemark);

                    const routeButton = new ymaps.control.Button({
                        data: { content: 'Проложить маршрут' },
                        options: { selectOnClick: false, maxWidth: 200 }
                    });

                    routeButton.events.add('click', function () {
                        navigator.geolocation.getCurrentPosition(function (position) {
                            const userCoords = [position.coords.latitude, position.coords.longitude];
                            ymaps.route([userCoords, shopCoords]).then(function (route) {
                                myMap.geoObjects.add(route);
                                const firstRoute = route.getRoutes().get(0);
                                if (firstRoute) {
                                    alert('Расстояние: ' + firstRoute.properties.get('distance').text +
                                          '\\nВремя: ' + firstRoute.properties.get('duration').text);
                                }
                            });
                        }, function () {
                            alert('Не удалось определить ваше местоположение.');
                        });
                    });

                    myMap.controls.add(routeButton, { float: 'left', floatIndex: 10 });
                });
            </script>
            HTML;
        });

        return Response::view(Common_View::layout(
            $content,
            title: Locale::get('layout.menu.map'),
            page_name: 'map',
            user: $user
        ));
    }
}

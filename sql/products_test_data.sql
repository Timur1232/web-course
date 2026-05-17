-- Категории
insert into categories default values; -- 1: guitars
insert into categories default values; -- 2: keyboards
insert into categories default values; -- 3: drums

-- Переводы категорий
insert into category_translations (category_id, lang_code, name) values
(1, 'ru', 'Гитары'),
(1, 'en', 'Guitars'),
(2, 'ru', 'Клавишные'),
(2, 'en', 'Keyboards'),
(3, 'ru', 'Ударные'),
(3, 'en', 'Drums');

-- Товары
insert into products (category_id, price, visible) values (1, 599.99, 1);
insert into products (category_id, price, visible) values (1, 899.99, 1);
insert into products (category_id, price, visible) values (2, 1299.99, 1);
insert into products (category_id, price, visible) values (3, 799.99, 1);

-- Переводы товаров (идентификаторы товаров: 1,2,3,4)
insert into product_translations (product_id, lang_code, name, description) values
(1, 'ru', 'Fender Stratocaster', 'Легендарная электрогитара.'),
(1, 'en', 'Fender Stratocaster', 'Legendary electric guitar.'),
(2, 'ru', 'Gibson Les Paul', 'Классическая электрогитара с мощным звуком.'),
(2, 'en', 'Gibson Les Paul', 'Classic electric guitar with powerful sound.'),
(3, 'ru', 'Yamaha P-45', 'Цифровое пианино с 88 клавишами.'),
(3, 'en', 'Yamaha P-45', 'Digital piano with 88 keys.'),
(4, 'ru', 'Roland TD-17', 'Электронная ударная установка.'),
(4, 'en', 'Roland TD-17', 'Electronic drum set.');

-- Первые изображения (для витрины)
insert into product_images (product_id, number, image_url) values
(1, 1, '/public/product_images/stratocaster.jpg'),
(2, 1, '/public/product_images/lespaul.jpg'),
(3, 1, '/public/product_images/yamaha_p45.jpg'),
(4, 1, '/public/product_images/roland_td17.jpg');

-- Гитары (id 1 и 2)
insert into product_images (product_id, number, image_url) values
(1, 2, '/public/product_images/stratocaster_back.jpg'),
(1, 3, '/public/product_images/stratocaster_head.jpg'),
(2, 2, '/public/product_images/lespaul_sunburst.jpg'),
(2, 3, '/public/product_images/lespaul_case.jpg');

-- Клавишные (id 3)
insert into product_images (product_id, number, image_url) values
(3, 2, '/public/product_images/yamaha_side.jpg');

-- Ударные (id 4)
insert into product_images (product_id, number, image_url) values
(4, 2, '/public/product_images/roland_module.jpg'),
(4, 3, '/public/product_images/roland_pads.jpg');

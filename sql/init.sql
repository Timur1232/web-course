create table if not exists users (
    login varchar(50) primary key,
    password_hash varchar(255) not null,
    email varchar(100) not null
);

create table if not exists user_privileges (
    user_login varchar(50) not null,
    privilege_name varchar(20) not null,

    primary key (user_login, privilege_name),
    foreign key (user_login) references users(login)
        on delete cascade
        on update cascade
);

create table if not exists news (
    id integer primary key autoincrement,
    date date not null,
    type varchar(10) default 'news',

    check (type in ('news', 'promotion'))
);

create table if not exists news_translations (
    news_id integer not null,
    lang_code varchar(10) not null,
    title varchar(100) not null,
    preview varchar(100) not null,
    content text,

    primary key (news_id, lang_code),
    foreign key (news_id) references news(id)
        on delete cascade
        on update cascade
);

create table if not exists categories (
    id integer primary key,
);

create table if not exists category_translations (
    category_id integer not null,
    lang_code varchar(10) not null,
    name varchar(150) not null,

    primary key (category_id, lang_code),
    foreign key (category_id) references categories(id)
        on delete cascade
        on update cascade
);

create table if not exists products (
    id integer primary key,
    category_id integer default null,
    price decimal(10,2) not null,

    foreign key (category_id) references categories(id)
        on delete set null
        on update cascade
);

create table if not exists product_translations (
    product_id integer not null,
    lang_code varchar(10) not null,
    name varchar(200) not null,
    description text,

    primary key (product_id, lang_code),
    foreign key (product_id) references products(id)
        on delete cascade
        on update cascade
);

create table if not exists product_images (
    id integer primary key,
    product_id integer not null,
    number integer not null,
    image_url varchar(255) not null,

    foreign key (product_id) references products(id)
        on delete cascade
        on update cascade
);

create table if not exists orders (
    id integer primary key,
    date timestamp default current_timestamp,
    customer_name varchar(150) not null,
    phone varchar(30) not null,
    email varchar(100) not null,
    payment_method varchar(100),
    delivery_method varchar(100),
    delivery_address varchar(255),
    status varchar(20) default 'new',
    total decimal(10,2)
);

create table if not exists ordered_products (
    id integer primary key,
    order_id integer not null,
    product_id integer,
    count integer not null,
    price decimal(10,2) not null,

    foreign key (order_id) references orders(id)
        on delete cascade
        on update cascade,
    foreign key (product_id) references products(id)
        on delete cascade
        on update cascade
);

create table if not exists reviews (
    id integer primary key,
    product_id integer not null,
    author_name varchar(100) not null,
    date timestamp default current_timestamp,
    text text not null,
    rating integer not null,

    check (rating between 1 and 5),

    foreign key (product_id) references products(id)
        on delete cascade
);

create table if not exists callback_messages (
    id integer primary key,
    name varchar(100) not null,
    email varchar(100) not null,
    message text not null,
    date timestamp default current_timestamp
);

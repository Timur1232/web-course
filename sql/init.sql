create table if not exists users (
    login varchar(50) primary key,
    password_hash varchar(255) not null,
    email varchar(100) not null
);

create table if not exists user_privileges (
    user_login varchar(50) not null,
    privilege_name varchar(100) not null,

    primary key (user_login, privilege_name),
    foreign key (user_login) references users(login)
        on delete cascade
);

create table if not exists pages (
    id serial primary key
);

create table if not exists page_translations (
    page_id integer not null,
    lang_code varchar(10) not null,
    content text not null,

    primary key (page_id, lang_code),
    foreign key (page_id) references pages(id)
        on delete cascade
);

create table if not exists news (
    id serial primary key,
    date date not null
);

create table if not exists news_translations (
    news_id integer not null,
    lang_code varchar(10) not null,
    title varchar(200) not null,
    content text,

    primary key (news_id, lang_code),
    foreign key (news_id) references news(id)
        on delete cascade
);

create table if not exists categories (
    id serial primary key
);

create table if not exists category_translations (
    category_id integer not null,
    lang_code varchar(10) not null,
    name varchar(150) not null,

    primary key (category_id, lang_code),
    foreign key (category_id) references categories(id)
        on delete cascade
);

create table if not exists products (
    id serial primary key,
    category_id integer not null,
    price decimal(10,2) not null,
    visible boolean default true,

    foreign key (category_id) references categories(id)
);

create table if not exists product_translations (
    product_id integer not null,
    lang_code varchar(10) not null,
    name varchar(200) not null,
    description text,

    primary key (product_id, lang_code),
    foreign key (product_id) references products(id)
        on delete cascade
);

create table if not exists product_images (
    id serial primary key,
    product_id integer not null,
    image_url varchar(255) not null,

    foreign key (product_id) references products(id)
        on delete cascade
);

create table if not exists orders (
    id serial primary key,
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
    id serial primary key,
    order_id integer not null,
    product_id integer not null,
    count integer not null,
    price decimal(10,2) not null,

    foreign key (order_id) references orders(id)
        on delete cascade,
    foreign key (product_id) references products(id)
);

create table if not exists reviews (
    id serial primary key,
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
    id serial primary key,
    name varchar(100) not null,
    email varchar(100) not null,
    message text not null,
    date timestamp default current_timestamp
);

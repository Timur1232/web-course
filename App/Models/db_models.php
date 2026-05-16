<?php namespace App\Models;
use App\Core\Model\AR_Field;
use App\Core\Model\Active_Record;
use DateTime;

#[Active_Record('users')]
final class User {
    public function __construct(
        #[AR_Field('login')]         public ?string $login         = null,
        #[AR_Field('email')]         public ?string $email         = null,
        #[AR_Field('password_hash')] public ?string $password_hash = null,
    ) {}
}

#[Active_Record('user_privileges')]
final class User_Privilege {
    public function __construct(
        #[AR_Field('user_login')]     public ?string $user_login = null,
        #[AR_Field('privilege_name')] public ?string $privilege_name = null,
    ) {}
}

#[Active_Record('pages')]
final class Page {
    public function __construct(
        #[AR_Field('id')] public ?int $id = null,
    ) {}
}

#[Active_Record('page_translations')]
final class Page_Translation {
    public function __construct(
        #[AR_Field('page_id')]   public ?int    $page_id   = null,
        #[AR_Field('lang_code')] public ?string $lang_code = null,
        #[AR_Field('content')]   public ?string $content   = null,
    ) {}
}

#[Active_Record('news')]
final class News {
    public function __construct(
        #[AR_Field('id')]   public ?int $id        = null,
        #[AR_Field('date')] public ?DateTime $date = null,
    ) {}
}

#[Active_Record('news_translations')]
final class News_Translation {
    public function __construct(
        #[AR_Field('news_id')]   public ?int    $news_id   = null,
        #[AR_Field('lang_code')] public ?string $lang_code = null,
        #[AR_Field('title')]     public ?string $title     = null,
        #[AR_Field('content')]   public ?string $content   = null,
    ) {}
}

#[Active_Record('categories')]
final class Category {
    public function __construct(
        #[AR_Field('id')] public ?int $id = null,
    ) {}
}

#[Active_Record('category_translations')]
final class Category_Translation {
    public function __construct(
        #[AR_Field('category_id')] public ?int    $category_id = null,
        #[AR_Field('lang_code')]   public ?string $lang_code   = null,
        #[AR_Field('name')]        public ?string $name        = null,
    ) {}
}

#[Active_Record('products')]
final class Product {
    public function __construct(
        #[AR_Field('id')]          public ?int   $id          = null,
        #[AR_Field('category_id')] public ?int   $category_id = null,
        #[AR_Field('price')]       public ?float $price       = null,
        #[AR_Field('visible')]     public ?bool  $visible     = null,
    ) {}
}

#[Active_Record('product_translations')]
final class Product_Translation {
    public function __construct(
        #[AR_Field('product_id')]  public ?int    $product_id  = null,
        #[AR_Field('lang_code')]   public ?string $lang_code   = null,
        #[AR_Field('name')]        public ?string $name        = null,
        #[AR_Field('description')] public ?string $description = null,
    ) {}
}

#[Active_Record('product_images')]
final class Product_Image {
    public function __construct(
        #[AR_Field('id')]         public ?int    $id         = null,
        #[AR_Field('product_id')] public ?int    $product_id = null,
        #[AR_Field('image_url')]  public ?string $image_url  = null,
    ) {}
}

#[Active_Record('orders')]
final class Order {
    public function __construct(
        #[AR_Field('id')]               public ?int      $id               = null,
        #[AR_Field('date')]             public ?DateTime $date             = null,
        #[AR_Field('customer_name')]    public ?string   $customer_name    = null,
        #[AR_Field('phone')]            public ?string   $phone            = null,
        #[AR_Field('email')]            public ?string   $email            = null,
        #[AR_Field('payment_method')]   public ?string   $payment_method   = null,
        #[AR_Field('delivery_method')]  public ?string   $delivery_method  = null,
        #[AR_Field('delivery_address')] public ?string   $delivery_address = null,
        #[AR_Field('status')]           public ?string   $status           = null,
        #[AR_Field('total')]            public ?float    $total            = null,
    ) {}
}

#[Active_Record('ordered_products')]
final class Ordered_Product {
    public function __construct(
        #[AR_Field('id')]         public ?int   $id         = null,
        #[AR_Field('order_id')]   public ?int   $order_id   = null,
        #[AR_Field('product_id')] public ?int   $product_id = null,
        #[AR_Field('count')]      public ?int   $count      = null,
        #[AR_Field('price')]      public ?float $price      = null,
    ) {}
}

#[Active_Record('reviews')]
final class Review {
    public function __construct(
        #[AR_Field('id')]          public ?int      $id          = null,
        #[AR_Field('product_id')]  public ?int      $product_id  = null,
        #[AR_Field('author_name')] public ?string   $author_name = null,
        #[AR_Field('date')]        public ?DateTime $date        = null,
        #[AR_Field('text')]        public ?string   $text        = null,
        #[AR_Field('rating')]      public ?int      $rating      = null,
    ) {}
}

#[Active_Record('callback_messages')]
final class Callback_Message {
    public function __construct(
        #[AR_Field('id')]      public ?int      $id      = null,
        #[AR_Field('name')]    public ?string   $name    = null,
        #[AR_Field('email')]   public ?string   $email   = null,
        #[AR_Field('message')] public ?string   $message = null,
        #[AR_Field('date')]    public ?DateTime $date    = null,
    ) {}
}

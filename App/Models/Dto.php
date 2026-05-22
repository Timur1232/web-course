<?php namespace App\Models\Dto;
use App\Core\Model\AR_Field;
use App\Core\Model\Active_Record;
use App\Core\Model\DB_Model;
use App\Core\Model\DB_Type;
use DateTime;

enum User_Privileges : string {
    case ADMIN    = 'admin';
    case CUSTOMER = 'customer';
}

#[Active_Record('users')]
final class User {
    public function __construct(
        #[AR_Field('login')]         public ?string         $login         = null,
        #[AR_Field('email')]         public ?string         $email         = null,
        #[AR_Field('password_hash')] public ?string         $password_hash = null,
                                     public User_Privileges $privilege     = User_Privileges::CUSTOMER,
    ) {}
}

#[Active_Record('user_privileges')]
final class User_Privilege {
    public function __construct(
        #[AR_Field('user_login')]     public ?string $user_login     = null,
        #[AR_Field('privilege_name')] public ?string $privilege_name = null,
    ) {}
}

#[Active_Record('news')]
final class News {
    public function __construct(
        #[AR_Field('id')]     public ?int $id        = null,
        #[AR_Field('date')]   public ?DateTime $date = null,
        #[AR_Field(('type'))] public ?string $type   = null,
    ) {}
}

#[Active_Record('news_translations')]
final class News_Translation {
    public function __construct(
        #[AR_Field('news_id')]   public ?int    $news_id   = null,
        #[AR_Field('lang_code')] public ?string $lang_code = null,
        #[AR_Field('title')]     public ?string $title     = null,
        #[AR_Field('preview')]   public ?string $preview   = null,
        #[AR_Field('content')]   public ?string $content   = null,
    ) {}
}

#[Active_Record]
final class Category {
    public function __construct(
        #[AR_Field('id')]   public ?int $id      = null,
        #[AR_Field('name')] public ?string $name = null,
    ) {}

    public static function select_all(): string {
        $sql = "
            select c.id, ct.name
            from categories c
            join category_translations ct on c.id = ct.category_id and ct.lang_code = :lang
            order by ct.name
        ";
        return match (DB_Model::$current_db) {
            DB_Type::MYSQL  => $sql,
            DB_Type::SQLITE => $sql,
        };
    }
}

#[Active_Record('category_translations')]
final class Category_Translation {
    public function __construct(
        #[AR_Field('category_id')] public ?int    $category_id = null,
        #[AR_Field('lang_code')]   public ?string $lang_code   = null,
        #[AR_Field('name')]        public ?string $name        = null,
    ) {}
}

#[Active_Record]
final class Product {
    public function __construct(
        #[AR_Field('id')]          public ?int    $id          = null,
        #[AR_Field('price')]       public ?float  $price       = null,
        #[AR_Field('name')]        public ?string $name        = null,
        #[AR_Field('description')] public ?string $description = null,
    ) {}

    public static function select_id(): string {
        $sql = "
            select p.id, pt.name, pt.description, p.price
            from products p
            join product_translations pt on p.id = pt.product_id and pt.lang_code = :lang
            where p.id = :id
        ";
        return match (DB_Model::$current_db) {
            DB_Type::MYSQL => $sql,
            DB_Type::SQLITE => $sql,
        };
    }
}

#[Active_Record]
final class Product_Showcase {
    public function __construct(
        #[AR_Field('id')]          public ?int    $id          = null,
        #[AR_Field('price')]       public ?float  $price       = null,
        #[AR_Field('name')]        public ?string $name        = null,
        #[AR_Field('image_url')]   public ?string $image_url   = null,
    ) {}

    public static function select_showcase(?int $category_id, ?string $search): string {
        $where_category = is_null($category_id) ? '' : 'p.category_id = :cat_id';
        $where_search = is_null($search) ? '' : 'pt.name like :search';
        if (!is_null($category_id) && !is_null($search)) $where_search = 'and ' . $where_search;
        $where = (is_null($category_id) && is_null($search)) ? '' : 'where';

        $sql = "
            select p.id, pt.name, p.price,
                (select image_url from product_images where product_id = p.id order by number asc limit 1) as image_url
            from products p
            join product_translations pt on p.id = pt.product_id and pt.lang_code = :lang
            {$where} {$where_category} {$where_search}
            order by p.id
        ";

        return match (DB_Model::$current_db) {
            DB_Type::MYSQL => $sql,
            DB_Type::SQLITE => $sql,
        };
    }
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
        #[AR_Field('number')]     public ?int    $number     = null,
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

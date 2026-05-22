<?php namespace App\Core\Model;
use ReflectionClass;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Active_Record {
    public function __construct(
        public ?string $table_name = null,
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class AR_Field {
    public function __construct(
        public string $column_name,
    ) {}
}

/*
 * @template T of object
 */
final class AR_Reflect {
    /*
     * @param array<string, AR_Field> $attrs
     * @param class-string<T> $class_name
     */
    public function __construct(
        public Active_Record $ar_attr,
        public string $class_name,
        public array $attrs = [],
    ) {}

    /*
     * @var array<string, AR_Reflect> $reflection_cache
     */
    public static array $reflection_cache = [];

    /*
     * @param class-string<T> $class_name
     * @param array<string, mixed> $data
     * @return T
     */
    public static function construct(string $class_name, array $data): mixed {
        $reflect = self::from($class_name);
        return $reflect?->construct_obj($data);
    }

    /*
     * @param class-string<T> $class_name
     * @param array<int, array<string, mixed>> $data
     * @return T
     */
    public static function construct_many(string $class_name, array $data): ?array {
        $reflect = self::from($class_name);
        if (is_null($reflect)) return null;
        $arr = [];
        foreach ($data as $row_data) {
            $arr[] = $reflect->construct_obj($row_data);
        }
        return $arr;
    }

    public static function table_name(string $class_name): ?string {
        return self::from($class_name)?->ar_attr->table_name;
    }

    public static function concat_column_names(string $class_name, string $separator = ',', bool $binding = true): ?string {
        $reflect = self::from($class_name);
        if (is_null($reflect)) return null;
        $str = '';
        $count = count($reflect->attrs);
        $i = 0;
        foreach ($reflect->attrs as $attr) {
            if ($binding) $str .= ':';
            $str .= $attr->column_name;
            if ($i < $count-1) $str .= $separator;
            $i += 1;
        }
        return $str;
    }

    public static function comma_separated_columns_string(string $class_name): ?string {
        return self::concat_column_names($class_name, ',', false);
    }

    public static function comma_separated_binding_string(string $class_name): ?string {
        return self::concat_column_names($class_name, ',', true);
    }

    public static function columns_values_array(mixed $class_obj): ?array {
        $reflect = self::from($class_obj::class);
        if (is_null($reflect)) return null;
        $arr = [];
        foreach ($reflect->attrs as $field => $attr) {
            $arr[$attr->column_name] = $class_obj->$field;
        }
        return $arr;
    }

    /*
     * @param class-string<T> $class_name
     */
    public static function from(string $class_name): ?self {
        if (array_key_exists($class_name, self::$reflection_cache)) {
            return self::$reflection_cache[$class_name];
        }
        /** @var ReflectionClass<T> $r */
        $r = new ReflectionClass($class_name);
        $ar_attr = self::get_ar_attribute($r);
        if (is_null($ar_attr)) return null;
        $self = new self($ar_attr, $class_name);
        foreach ($r->getProperties() as $prop) {
            foreach ($prop->getAttributes() as $prop_attr) {
                if ($prop_attr->getName() === AR_Field::class) {
                    $attr = $prop_attr->newInstance();
                    $self->attrs[$prop->getName()] = $attr;
                }
            }
        }
        self::$reflection_cache[$class_name] = $self;
        return $self;
    }

    public function column_value_pairs_array(mixed $class_obj): array {
        $arr = [];
        foreach ($this->fields_to_columns_array() as $field => $column) {
            $arr[$column] = $class_obj->$field;
        }
        return $arr;
    }

    /*
     * @return array<string, string>
     */
    public function fields_to_columns_array(): array {
        return array_map(fn($v) => $v->column_name, $this->attrs);
    }

    /*
     * @param array<string, mixed> $data
     * @return T
     */
    public function construct_obj(array $data): mixed {
        $obj = new $this->class_name;
        foreach ($this->fields_to_columns_array() as $field_name => $column_name) {
            $obj->$field_name = $data[$column_name];
        }
        return $obj;
    }

    /*
     * @param ReflectionClass<T> $r
     */
    private static function get_ar_attribute(ReflectionClass $r): ?Active_Record {
        /** @var ?Active_Record $ar */
        $ar = null;
        foreach ($r->getAttributes() as $attr) {
            if ($attr->getName() === Active_Record::class) {
                $ar = $attr->newInstance();
                break;
            }
        }
        return $ar;
    }
}

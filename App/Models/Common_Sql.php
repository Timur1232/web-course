<?php namespace App\Models\Common_Sql;
use App\Core\Helpers\Error;
use App\Core\Model\AR_Field;
use App\Core\Model\AR_Reflect;
use App\Core\Model\Active_Record;
use App\Core\Test\Test;

enum Order_Dir: string {
    case ASC = 'asc';
    case DESC = 'desc';
}

final class Order {
    public function __construct(
        public string $column,
        public Order_Dir $direction,
    ) {}

    public static function asc(string $column): self {
        return new self($column, Order_Dir::ASC);
    }

    public static function desc(string $column): self {
        return new self($column, Order_Dir::DESC);
    }
}

final class Common_Sql {
    public static function select(string|array $source, ?string $table = null, ?string $where = null, ?Order $order_by = null): string {
        if (is_array($source) && $table === null) {
            Error::assert(false, __METHOD__.': Table name is required when passing an array of columns.');
        }

        self::resolve_table_and_columns($source, $table, $columns);

        $column_list = implode(', ', $columns);
        $sql = "select {$column_list} from {$table}";

        if (!is_null($where)) {
            $sql .= " where {$where}";
        }

        if (!is_null($order_by)) {
            $sql .= ' order by ' . $order_by->column . ' ' . $order_by->direction->value;
        }

        return $sql;
    }

    public static function insert(string|array $source, ?string $table = null, int $count = 1): string {
        if (is_array($source) && $table === null) {
            Error::assert(false, __METHOD__.': Table name is required when passing an array of columns.');
        }

        $columns = [];
        self::resolve_table_and_columns($source, $table, $columns);

        $column_list = implode(', ', $columns);
        $binding_list = '';
        if ($count === 1) {
            $binding_list = '(' . implode(', ', array_map(fn(string $col) => ":{$col}", $columns)) . ')';
        } else {
            for ($i = 0; $i < $count; $i += 1) {
                $binding_list .= '(' . implode(', ', array_map(fn(string $col) => ":{$col}{$i}", $columns)) . ')';
                if ($i < $count-1) {
                    $binding_list .= ', ';
                }
            }
        }

        return "insert into {$table} ({$column_list}) values {$binding_list}";
    }

    public static function update(string|array $source, ?string $table = null, ?string $where = null): string {
        if (is_array($source) && $table === null) {
            Error::assert(false, __METHOD__.': Table name is required when passing an array of columns.');
        }

        $columns = [];
        self::resolve_table_and_columns($source, $table, $columns);

        $set_parts = implode(', ', array_map(fn(string $col) => "{$col} = :{$col}", $columns));

        $sql = "update {$table} set {$set_parts}";

        if (!is_null($where)) {
            $sql .= " where {$where}";
        }

        return $sql;
    }

    public static function delete(string $table, string $where): string {
        $sql = "delete from {$table}";
        $sql .= " where {$where}";
        return $sql;
    }

    private function __construct() {}

    private static function resolve_table_and_columns(string|array $source, ?string &$table, ?array &$columns): void {
        if (is_string($source)) {
            $reflect = AR_Reflect::from($source);
            if ($reflect === null) {
                Error::assert(false, __METHOD__.": Class {$source} does not have #[Active_Record] attribute.");
            }
            $table = $reflect->ar_attr->table_name;
            $mapping = $reflect->fields_to_columns_array();
            $columns = array_values($mapping);
        } elseif (is_array($source)) {
            $columns = $source;
        } else {
            Error::assert(false, __METHOD__.': Source must be a class-string or an array of column names.');
        }
    }

     #[Test('select with class and where')]
    private static function test_select_with_class(): void {
        $sql = self::select(TestRecord::class, where: 'a = 5');
        Test::assert($sql === 'select a, b from test where a = 5', "Unexpected SQL: {$sql}");
    }

    #[Test('select with array of columns')]
    private static function test_select_with_array(): void {
        $sql = self::select(['urmom', 'aboba', 'bebebebobobo'], table: 'hell_yeah');
        Test::assert($sql === 'select urmom, aboba, bebebebobobo from hell_yeah', "Unexpected SQL: {$sql}");
    }

    #[Test('insert with class')]
    private static function test_insert_with_class(): void {
        $sql = self::insert(TestRecord::class);
        Test::assert($sql === 'insert into test (a, b) values (:a, :b)', "Unexpected SQL: {$sql}");
    }

    #[Test('insert with array of columns')]
    private static function test_insert_with_array(): void {
        $sql = self::insert(['urmom', 'aboba', 'bebebebobobo'], table: 'hell_yeah');
        Test::assert($sql === 'insert into hell_yeah (urmom, aboba, bebebebobobo) values (:urmom, :aboba, :bebebebobobo)', "Unexpected SQL: {$sql}");
    }

    #[Test('update with class and where')]
    private static function test_update_with_class(): void {
        $sql = self::update(TestRecord::class, where: 'a = 5');
        Test::assert($sql === 'update test set a = :a, b = :b where a = 5', "Unexpected SQL: {$sql}");
    }

    #[Test('update with array of columns (no where)')]
    private static function test_update_with_array(): void {
        $sql = self::update(['urmom', 'aboba', 'bebebebobobo'], table: 'hell_yeah');
        Test::assert($sql === 'update hell_yeah set urmom = :urmom, aboba = :aboba, bebebebobobo = :bebebebobobo', "Unexpected SQL: {$sql}");
    }

    #[Test('delete with table from class and where')]
    private static function test_delete_with_class_table(): void {
        $table = \App\Core\Model\AR_Reflect::table_name(TestRecord::class);
        $sql = self::delete($table, 'a = 5');
        Test::assert($sql === 'delete from test where a = 5', "Unexpected SQL: {$sql}");
    }

    #[Test('delete with literal table and empty where')]
    private static function test_delete_with_empty_where(): void {
        $sql = self::delete('hell_yeah', '');
        Test::assert($sql === 'delete from hell_yeah where ', "Unexpected SQL: {$sql}");
    }
}

#[Active_Record('test')]
class TestRecord {
    public function __construct(
        #[AR_Field('a')] public ?int $a = null,
        #[AR_Field('b')] public ?string $b = null,
    ) {}
}

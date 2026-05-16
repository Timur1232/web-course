<?php namespace App\Core\Model;
use App\Core\Helpers\Result;
use PDO;
use Pdo\Sqlite;
use App\Core\Helpers\CSVFile;
use App\Core\Helpers\Log;
use App\Core\Test\Test;

/*
 * @template T
 */
interface ARModel {
    /*
     * @param class-string<T> $class_name
     * @return Result<T[]>
     */
    function find_all(string $class_name): Result;
    /*
     * @param class-string<T> $class_name
     * @return Result<?T>
     */
    function find_by_id(string $class_name, mixed $id): Result;
    /*
     * @param T|T[] $class_obj
     */
    function insert(mixed $class_obj): Result;
    /*
     * @return Result<int>
     */
    function update_by_id(mixed $class_obj): Result;
    /*
     * @param class-string<T> $class_name
     * @return Result<int>
     */
    function delete_by_id(string $class_name, mixed $id): Result;
}

/**
 * @implements ARModel<T>
 */
final class DBModel implements ARModel {
    public function __construct(
        private PDO $conn,
        private ARQueryBuilder $query,
    ) {}

    public static function sqlite(string $db_path): self {
        return new self(new Sqlite("sqlite:{$db_path}"), new SQLiteQueryBuilder());
    }

    /*
     * @template T
     * @param class-string<T> $class_name
     * @return Result<T[]>
     */
    public function find_all(string $class_name, int $limit = 0): Result {
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        }

        $sql = $this->query->select($props, limit: $limit);
        /** @var PDOStatement $stmt */
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return Result::ERROR(__METHOD__.": Unable to prepare an sql statement");
        if (!$stmt->execute()) {
            return Result::ERROR(__METHOD__.': '.$this->conn->errorInfo());
        }
        $rows = $stmt->fetchAll();
        if ($rows === false) return Result::ERROR(__METHOD__.": Unable to fetch result");
        return Result::OK(array_map(fn($row) => $props->construct_obj($row), $rows));
    }

    /*
     * @template T
     * @param class-string<T> $class_name
     * @return Result<?T>
     */
    public function find_by_id(string $class_name, $id, int $limit = 1): Result {
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        } else if (!$props->has_id()) {
            return Result::ERROR(__METHOD__.": ID property must be set to find by id in {$class_name}");
        }

        $sql = $this->query->select_by_id($props, limit: $limit);
        /** @var PDOStatement $stmt */
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return Result::ERROR(__METHOD__.": Unable to prepare an sql statement");
        $stmt->bindValue(':id', $id);
        if (!$stmt->execute()) {
            return Result::ERROR($this->conn->errorInfo()[2]);
        }
        $row = $stmt->fetch();
        if ($row === false) return Result::ERROR(__METHOD__.": Unable to fetch result");
        return Result::OK($props->construct_obj($row));
    }

    /*
     * @template T
     * @param T|T[] $class_obj
     */
    public function insert(mixed $class_obj): Result {
        if (is_array($class_obj) && count($class_obj) <= 0) {
            return Result::ERROR(__METHOD__.": Array must have at least one item");
        }

        $class_name = '';
        $count = 1;
        if (is_array($class_obj)) {
            $class_name = array_first($class_obj)::class;
            $count = count($class_obj);
        } else {
            $class_name = $class_obj::class;
        }
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        }

        $sql = $this->query->insert($props, $count);
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return Result::ERROR(__METHOD__.": Unable to prepare an sql statement");
        if (is_array($class_obj)) {
            $i = 0;
            foreach ($class_obj as $obj) {
                foreach ($props->normalized() as $field => $col) {
                    $stmt->bindValue(":{$col}{$i}", $obj->$field);
                }
                $i++;
            }
        } else {
            foreach ($props->normalized() as $field => $col) {
                $stmt->bindValue(":{$col}0", $class_obj->$field);
            }
        }
        if (!$stmt->execute()) {
            return Result::ERROR($this->conn->errorInfo()[2]);
        }
        return Result::OK();
    }

    /*
     * @return Result<int>
     */
    public function update_by_id(mixed $class_obj): Result {
        $class_name = $class_obj::class;
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        } else if (!$props->has_id()) {
            return Result::ERROR(__METHOD__.": ID property must be set to find by id in {$class_name}");
        }

        $sql = $this->query->update_by_id($props);
        /** @var PDOStatement $stmt */
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return Result::ERROR(__METHOD__.": Unable to prepare an sql statement");
        $id_field = $props->get_id_attr_norm()[0];
        $stmt->bindValue(':id', $class_obj->$id_field);
        foreach ($props->normalized() as $field => $col) {
            $stmt->bindValue(":$col", $class_obj->$field);
        }
        if (!$stmt->execute()) {
            return Result::ERROR($this->conn->errorInfo()[2]);
        }
        return Result::OK($stmt->rowCount());
    }

    /*
     * @template T
     * @param class-string<T> $class_name
     * @return Result<int>
     */
    public function delete_by_id(string $class_name, $id): Result {
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        } else if (!$props->has_id()) {
            return Result::ERROR(__METHOD__.": ID property must be set to find by id in {$class_name}");
        }

        $sql = $this->query->delete_by_id($props);
        /** @var PDOStatement $stmt */
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return Result::ERROR(__METHOD__.": Unable to prepare an sql statement");
        $stmt->bindValue(':id', $id);
        if (!$stmt->execute()) {
            return Result::ERROR($this->conn->errorInfo()[2]);
        }
        return Result::OK($stmt->rowCount());
    }
}

/*
 * @template T
 * @implements ARModel<T>
 */
final class FileCSVModel implements ARModel {
    public function __construct(
        public CSVFile $csv,
    ) {}

    /*
     * @param class-string<T> $class_name
     * @return Result<self>
     */
    public static function open_or_create(string $file_path, string $class_name, string $sep = ';'): Result {
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        }

        $head = array_values($props->normalized());

        if (file_exists($file_path)) {
            Log::warning(__METHOD__.": db file {$file_path} already exists. Opening it.");
            return self::open($file_path, sep: $sep, expected_head: $head);
        }

        $res = CSVFile::open_or_create($file_path, head: $head, sep: $sep);
        if (!$res->ok) return $res;
        return Result::OK(new self($res->val));
    }

    /**
     * @param string[]|null|class-string<T> $expected_head -- head for validating, pass null for no validation
     * @return Result<self>
     */
    public static function open(string $file_path, string $sep = ';', mixed $expected_head = null): Result {
        if (is_string($expected_head)) {
            $props = ARAttributes::from($expected_head);
            if (!isset($props)) {
                return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$expected_head}");
            }
            $expected_head = array_values($props->normalized());
        }
        $res = CSVFile::open($file_path, sep: $sep, expected_head: $expected_head);
        if (!$res->ok) return $res;
        return Result::OK(new self($res->val));
    }

    /*
     * @param class-string<T> $class_name
     */
    public function validate(string $class_name): bool {
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return false;
        }
        foreach ($props->normalized() as $v) {
            if (!in_array($v, $this->csv->head)) {
                return false;
            }
        }
        return true;
    }

    /*
     * @param class-string<T> $class_name
     * @return Result<T[]>
     */
    public function find_all(string $class_name): Result {
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        }
        $objs = [];
        foreach ($this->csv->combine_key_value() as $row) {
            $objs[] = $props->construct_obj($row);
        }
        return Result::OK($objs);
    }

    /*
     * @param class-string<T> $class_name
     * @return Result<T[]>
     */
    public function find_by_id(string $class_name, $id): Result {
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        } else if (!$props->has_id()) {
            return Result::ERROR(__METHOD__.": ID property must be set to find by id in {$class_name}");
        }

        [$_, $id_column_name] = $props->get_id_attr_norm();
        $res = $this->csv->find([
            $id_column_name => $id
        ]);
        if (!$res->ok) return $res;

        return Result::OK(array_map(fn($v) => $props->construct_obj($v), $res->val));
    }

    /*
     * @param T|T[] $class_obj
     */
    public function insert(mixed $class_obj): Result {
        if (is_array($class_obj) && count($class_obj) <= 0) {
            return Result::ERROR(__METHOD__.": Array must have at least one item");
        }
        $class_name = '';
        if (is_array($class_obj)) {
            $class_name = array_first($class_obj)::class;
        } else {
            $class_name = $class_obj::class;
        }
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        }
        if (is_array($class_obj)) {
            return $this->csv->append(array_map(fn($v) => $props->as_array($v), $class_obj));
        } else {
            return $this->csv->append([$props->as_array($class_obj)]);
        }
    }

    /*
     * @return Result<int>
     */
    public function update_by_id(mixed $class_obj): Result {
        $class_name = $class_obj::class;
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        } else if (!$props->has_id()) {
            return Result::ERROR(__METHOD__.": ID property must be set to update by id in {$class_name}");
        }

        [$id_field_name, $id_column_name] = $props->get_id_attr_norm();
        return $this->csv->update(
            [$id_column_name => $class_obj->$id_field_name],
            $props->as_array($class_obj),
        );
    }

    /*
     * @param class-string<T> $class_name
     * @return Result<int>
     */
    public function delete_by_id(string $class_name, $id): Result {
        $props = ARAttributes::from($class_name);
        if (!isset($props)) {
            return Result::ERROR(__METHOD__.": No ActiveRecord attribute on class {$class_name}");
        } else if (!$props->has_id()) {
            return Result::ERROR(__METHOD__.": ID property must be set to delete by id in {$class_name}");
        }

        [$_, $id_column_name] = $props->get_id_attr_norm();
        return $this->csv->delete(
            [$id_column_name => $id],
        );
    }

    private static function test_class(?int $a = null, ?string $b = null): mixed {
        $c = new #[ActiveRecord('test_table')] class {
            #[ARField('column_a', ARField::ID_FIELD)] public ?int $a    = null;
            #[ARField('column_b')]                    public ?string $b = null;
        };
        $c->a = $a;
        $c->b = $b;
        return $c;
    }

    private static string $test_file_path = 'test/test.inc';

    private static function test_delete_db_file(): void {
        if (file_exists(self::$test_file_path)) unlink(self::$test_file_path);
    }

    private static function test_preparsed_ar_classes(): array {
        return array_map(function($arr) {
            [$a, $b] = $arr;
            return self::test_class((int)$a, $b);
        }, [
            ['5', 'hello'],
            ['-23', 'hell yeah'],
            ['69', 'urmom'],
            ['420', '; ; ;escape; ; ;'],
            ['5', 'hello'],
            ['5', 'hello'],
            ['69', 'urmom'],
        ]);
    }

    private static function test_create_db_file(): void {
        self::test_delete_db_file();
        file_put_contents(self::$test_file_path, <<<STR
            column_a;column_b
            5;hello
            -23;hell yeah
            69;urmom
            420;\; \; \;escape\; \; \;
            5;hello
            5;hello
            69;urmom\n
            STR);
    }

    private static function test_open_empty_db(): self {
        self::test_delete_db_file();
        $res = FileCSVModel::open_or_create(self::$test_file_path, self::test_class()::class);
        Test::expect_ok($res);
        return $res->val;
    }

    private static function test_open_full_db(): self {
        self::test_create_db_file();
        $res = FileCSVModel::open(self::$test_file_path, expected_head: self::test_class()::class);
        Test::expect_ok($res);
        return $res->val;
    }

    #[Test('insert one')]
    private static function test_insert_one(): void {
        $model = self::test_open_empty_db();
        $class = self::test_class(5, 'hello');

        $model->insert($class);
        Test::match_file_contents(self::$test_file_path, <<<STR
            column_a;column_b
            5;hello\n
            STR, 'first insert');

        $class = self::test_class(-23, 'hell yeah');

        $model->insert($class);
        Test::match_file_contents(self::$test_file_path, <<<STR
            column_a;column_b
            5;hello
            -23;hell yeah\n
            STR, 'second insert');
    }

    #[Test('insert many')]
    private static function test_insert_many(): void {
        $model = self::test_open_empty_db();
        $class1 = self::test_class(5, 'hello');
        $class2 = self::test_class(-23, 'hell yeah');

        $model->insert([$class1, $class2]);
        Test::match_file_contents(self::$test_file_path, <<<STR
            column_a;column_b
            5;hello
            -23;hell yeah\n
            STR, 'many insert');
    }

    #[Test('insert escaped')]
    private static function test_insert_escaped(): void {
        $model = self::test_open_empty_db();
        $class = self::test_class(5, '; ; ;escape; ; ;');
        $model->insert($class);
        Test::match_file_contents(self::$test_file_path, <<<STR
            column_a;column_b
            5;\; \; \;escape\; \; \;\n
            STR, 'escaped');
    }

    #[Test('find all')]
    private static function test_find_all(): void {
        $model = self::test_open_full_db();

        $res2 = $model->find_all(self::test_class()::class);
        Test::expect_ok($res2);
        $objects = $res2->val;

        Test::match_arrays_values($objects, self::test_preparsed_ar_classes());
    }

    #[Test('find by id')]
    private static function test_find_by_id(): void {
        $model = self::test_open_full_db();

        $res2 = $model->find_by_id(self::test_class()::class, 5);
        Test::expect_ok($res2);
        $objects = $res2->val;
        Test::assert(count($objects) === 3, 'expected 3 objects with id = 5');

        foreach ($objects as $obj) {
            Test::assert($obj->a === 5, 'object should have a = 5');
            Test::assert($obj->b === 'hello', 'object should have b = \'hello\'');
        }

        $res3 = $model->find_by_id(self::test_class()::class, 69);
        Test::expect_ok($res3);
        $objects69 = $res3->val;
        Test::assert(count($objects69) === 2, 'expected 2 objects with id = 69');

        $res4 = $model->find_by_id(self::test_class()::class, 420);
        Test::expect_ok($res4);
        $objects420 = $res4->val;
        Test::assert(count($objects420) === 1, 'expected 1 object with id = 420');
        Test::assert($objects420[0]->b === '; ; ;escape; ; ;', 'check escaped string');

        $res5 = $model->find_by_id(self::test_class()::class, 999);
        Test::expect_ok($res5);
        $objects999 = $res5->val;
        Test::assert(count($objects999) === 0, 'expected 0 objects for non existing id');
    }

    #[Test('delete by id')]
    private static function test_delete_by_id(): void {
        $model = self::test_open_full_db();

        $res2 = $model->delete_by_id(self::test_class()::class, 5);
        Test::expect_ok($res2);
        $deleted_count = $res2->val;
        Test::assert($deleted_count === 3, 'deleted count should be 3');

        $lines = file(self::$test_file_path, FILE_IGNORE_NEW_LINES);
        $data_lines = array_slice($lines, 1);
        Test::assert(count($data_lines) === 4, 'after delete, should have 4 data lines');

        foreach ($data_lines as $line) {
            Test::assert(strpos($line, '5;') === false, 'line should not contain id = 5');
        }

        $res3 = $model->delete_by_id(self::test_class()::class, 69);
        Test::expect_ok($res3);
        $deleted_count2 = $res3->val;
        Test::assert($deleted_count2 === 2, 'deleted count should be 2 for id = 69');

        $res4 = $model->delete_by_id(self::test_class()::class, 999);
        Test::expect_ok($res4);
        $deleted_count3 = $res4->val;
        Test::assert($deleted_count3 === 0, 'deleted count should be 0 for non existing id');
    }

    #[Test('update by id')]
    private static function test_update_by_id(): void {
        $model = self::test_open_full_db();

        $class_to_update = self::test_class(5, 'updated');
        $res2 = $model->update_by_id($class_to_update);
        Test::expect_ok($res2);
        $updated_count = $res2->val;
        Test::assert($updated_count === 3, 'updated count should be 3 for id = 5');

        $find = $model->find_all($class_to_update::class);
        Test::expect_ok($find);
        $expected = array_map(function($v) {
            if ($v->a === 5) $v->b = 'updated';
            return $v;
        }, self::test_preparsed_ar_classes());
        Test::match_arrays_values($find->val, $expected);

        $class_to_update69 = self::test_class(69, 'modified');
        $res3 = $model->update_by_id($class_to_update69);
        Test::expect_ok($res3);
        $updated_count2 = $res3->val;
        Test::assert($updated_count2 === 2, 'updated count should be 2 for id = 69');

        $class_non_exist = self::test_class(999, 'nothing');
        $res4 = $model->update_by_id($class_non_exist);
        Test::expect_ok($res4);
        $updated_count3 = $res4->val;
        Test::assert($updated_count3 === 0, 'updated count should be 0 for non existing id');
    }

    #[Test('invalid: missing ActiveRecord attribute')]
    private static function test_invalid_missing_attr(): void {
        $bad_class = new class {
            public int $a;
        };
        $bad_class_name = $bad_class::class;

        $res = FileCSVModel::open_or_create(self::$test_file_path, $bad_class_name);
        Test::expect_error($res);

        $model = self::test_open_full_db();

        $res2 = $model->find_all($bad_class_name);
        Test::expect_error($res2);

        $res3 = $model->insert($bad_class);
        Test::expect_error($res3);

        $res4 = $model->update_by_id($bad_class);
        Test::expect_error($res4);

        $res5 = $model->delete_by_id($bad_class_name, 5);
        Test::expect_error($res5);
    }

    #[Test('invalid: missing ID field')]
    private static function test_invalid_missing_id(): void {
        $class_without_id = new #[ActiveRecord('test')] class {
            public function __construct(
                #[ARField('col_a')] public int $a = 5,
            ) {}
        };
        $class_name = $class_without_id::class;

        $temp_file = self::$test_file_path . '.missing_id.csv';
        if (file_exists($temp_file)) unlink($temp_file);
        $res_open = FileCSVModel::open_or_create($temp_file, $class_name);
        Test::expect_ok($res_open);
        $model = $res_open->val;

        $res = $model->find_by_id($class_name, 1);
        Test::expect_error($res, 'find_by_id should fail because no ID field');

        $obj = new $class_without_id(1);
        $res2 = $model->update_by_id($obj);
        Test::expect_error($res2, 'update_by_id should fail because no ID field');

        $res3 = $model->delete_by_id($class_name, 1);
        Test::expect_error($res3, 'delete_by_id should fail because no ID field');

        unlink($temp_file);
    }
}

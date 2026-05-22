<?php namespace App\Core\Model;

use App\Core\Helpers\Log;
use App\Core\Helpers\Result;
use Exception;
use PDO;
use PDOStatement;
use Pdo\Mysql;
use Pdo\Sqlite;

enum DB_Type {
    case SQLITE;
    case MYSQL;
}

final class DB_Stmt {
    public function __construct(
        public ?PDOStatement $stmt = null,
    ) {}

    /**
    * @return Result<self>
    */
    public function execute(): Result {
        if (is_null($this->stmt)) return Result::ERROR('pdo statement is null');
        $res = false;
        try {
            $res = $this->stmt->execute();
        } catch (Exception $e) {
            $err = $this->stmt->errorInfo();
            Log::error(__METHOD__.": {$e->getMessage()}\n{$err}\n{$e->getTraceAsString()}");
            return Result::ERROR('error during executing, info: '.print_r($err, true));
        }
        if ($res === false) return Result::ERROR('error during executing, info: '.print_r($this->stmt->errorInfo(), true));
        return Result::OK($this);
    }

    public function rows_count(): int {
        if (is_null($this->stmt)) return 0;
        return $this->stmt->rowCount();
    }

    /**
    * @return Result<array<string, string>>
    */
    public function fetch(): Result {
        if (is_null($this->stmt)) return Result::ERROR('pdo statement is null');
        $execute_res = $this->execute();
        if (!$execute_res->ok) return $execute_res;
        $ret = false;
        try {
            $ret = $this->stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $err = $this->stmt->errorInfo();
            Log::error(__METHOD__.": {$e->getMessage()}\n{$err}\n{$e->getTraceAsString()}");
            return Result::ERROR('error during fetching, info: '.print_r($err, true));
        }
        if ($ret === false) return Result::ERROR('error during fetching, info: '.print_r($this->stmt->errorInfo(), true));
        return Result::OK($ret);
    }

    /**
    * @return Result<array<int, array<string, string>>>
    */
    public function fetch_all(): Result {
        if (is_null($this->stmt)) return Result::ERROR('pdo statement is null');
        $execute_res = $this->execute();
        if (!$execute_res->ok) return $execute_res;
        $ret = null;
        try {
            $ret = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $err = $this->stmt->errorInfo();
            Log::error(__METHOD__.": {$e->getMessage()}\n{$err}\n{$e->getTraceAsString()}");
            return Result::ERROR('error during fetching, info: '.print_r($err, true));
        }
        return Result::OK($ret);
    }

    /**
     * @param mixed|array<int|string,mixed> $data
     * @return Result<self>
     */
    public function bind_values(mixed $data, bool $numbered = false): Result {
        if (is_null($this->stmt)) return Result::ERROR('pdo statement is null');

        $vals = [];
        if (is_array($data)) {
            $vals = $data;
        } else {
            $ar = AR_Reflect::from($data::class);
            if (is_null($ar)) return Result::ERROR('unable to reflect on data, type: ' . gettype($data));
            foreach ($ar->fields_to_columns_array() as $field => $column) {
                $vals[$column] = $data->$field;
            }
        }

        $i = 0;
        try {
            foreach ($vals as $index => $val) {
                if (is_int($index)) {
                    if (!$this->stmt->bindValue($i+1, $val)) {
                        return Result::ERROR('unable to bind value with index = '.print_r($index, true));
                    }
                } else if (is_string($index)) {
                    if ($numbered) $index .= strval($i);
                    if (!$this->stmt->bindValue($index, $val)) {
                        return Result::ERROR('Unable to bind value with index = '.print_r($index, true));
                    }
                } else {
                    return Result::ERROR('Incorrect index type: '.print_r($index, true));
                }
                $i += 1;
            }
        } catch (Exception $e) {
            $err = $this->stmt->errorInfo();
            Log::error(__METHOD__.": {$e->getMessage()}\n{$err}\n{$e->getTraceAsString()}");
            return Result::ERROR('error during binding, info: '.print_r($err, true));
        }
        return Result::OK($this);
    }

    /**
    * @param array<mixed> $data
    * @return Result<self>
    */
    public function bind_many_values(array $data): Result {
        if (is_null($this->stmt)) return Result::ERROR('pdo statement is null');
        foreach ($data as $obj) {
            $res = $this->bind_values($obj);
            if (!$res->ok) return $res;
        }
        return Result::OK($this);
    }
}

final class DB_Model {
    public static PDO $conn;
    public static DB_Type $current_db;

    public static function my_sql_connect(string $host, int $port, string $db_name, ?string $username = null, ?string $password = null, ?array $options = null): void {
        self::$conn = new Mysql("mysql:host={$host};port={$port};dbname={$db_name}", $username, $password, $options);
        self::$current_db = DB_Type::MYSQL;
    }

    public static function sqlite_connect(string $db_path): void {
        self::$conn = new Sqlite("sqlite:{$db_path}");
        self::$current_db = DB_Type::SQLITE;
    }

    /**
    * @return Result<DB_Stmt>
    */
    public static function query(string $sql): Result {
        $stmt = false;
        try {
            $stmt = self::$conn->prepare($sql);
        } catch (Exception $e) {
            Log::error(__METHOD__.": $sql; {$e->getMessage()}\n{$e->getTraceAsString()}");
            return Result::ERROR('unable to prepare query: '.$sql);
        }
        if ($stmt === false) return Result::ERROR('unable to prepare query: '.$sql);
        return Result::OK(new DB_Stmt($stmt));
    }

    public static function begin_transaction(): bool {
        return self::$conn->beginTransaction();
    }

    public static function commit(): bool {
        return self::$conn->commit();
    }

    public static function roll_back(): bool {
        return self::$conn->rollBack();
    }

    private function __construct() {}
}

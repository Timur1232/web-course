<?php namespace App\Core\Model;
use App\Core\Helpers\Log;
use PDO;
use PDOStatement;
use Pdo\Mysql;
use Pdo\Sqlite;

final class DB_Stmt {
    public function __construct(
        public ?PDOStatement $stmt = null,
    ) {}

    public function execute(): ?self {
        if (is_null($this->stmt)) return null;
        $res = $this->stmt->execute();
        if ($res === false) return null;
        return $this;
    }

    public function rows_count(): int {
        if (is_null($this->stmt)) return 0;
        return $this->stmt->rowCount();
    }

    public function fetch(): ?array {
        if (is_null($this->stmt)) return null;
        $ret = $this->stmt->fetch(PDO::FETCH_ASSOC);
        if ($ret === false) return null;
        return $ret;
    }

    public function fetch_all(): ?array {
        if (is_null($this->stmt)) return null;
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<int|string,mixed> $vals
     */
    public function bind_values(array $vals): ?self {
        if (is_null($this->stmt)) return null;
        $count = 1;
        foreach ($vals as $index => $val) {
            if (is_int($index)) {
                if (!$this->stmt->bindValue($count, $val)) {
                    Log::error(__METHOD__.": Unable to bind value with index = ".print_r($index, true));
                    return null;
                }
                $count += 1;
            } else if (is_string($index)) {
                if (!$this->stmt->bindValue($index, $val)) {
                    Log::error(__METHOD__.": Unable to bind value with index = ".print_r($index, true));
                    return null;
                }
            } else {
                Log::error(__METHOD__.": Incorrect index type: ".print_r($index, true));
                return null;
            }
        }
        return $this;
    }
}

final class DB_Model {
    public static PDO $conn;

    public static function my_sql_connect(string $conn_string, ?string $username = null, ?string $password = null, ?array $options = null): void {
        self::$conn = new Mysql($conn_string, $username, $password, $options);
    }

    public static function sqlite_connect(string $db_path): void {
        self::$conn = new Sqlite("sqlite:{$db_path}");
    }

    public static function query(string $sql): ?DB_Stmt {
        $stmt = self::$conn->prepare($sql);
        if ($stmt === false) return null;
        return new DB_Stmt($stmt);
    }

    private function __construct() {}
}

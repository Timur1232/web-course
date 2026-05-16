<?php
require_once('./init.php');
require __DIR__.'/vendor/autoload.php';
spl_autoload_register(Init::autoload(...));

use App\Controllers\Index;
use App\Core\Context\Router;
use App\Core\Model\AR_Field;
use App\Core\Model\AR_Reflect;
use App\Core\Model\Active_Record;
use App\Core\Model\DB_Model;
use App\Models\User;

DB_Model::sqlite_connect('test.db');

#[Active_Record('test')]
class TestRecord {
    public function __construct(
        #[AR_Field('a')] public ?int $a = null,
        #[AR_Field('b')] public ?string $b = null,
    ) {}
}

$test = new TestRecord(420, 'aboba');

/* var_dump(DB_Model::query("insert into test (" */
/*         .AR_Reflect::comma_separated_columns_string($test::class) */
/*     .") values (" */
/*         .AR_Reflect::comma_separated_binding_string($test::class) */
/*     .")")?->bind_values(AR_Reflect::columns_values_array($test))?->execute()); */

print_r(AR_Reflect::construct_many(TestRecord::class, DB_Model::query("select * from test")?->execute()?->fetch()));

die();

Router::setup_current_request();

Router::GET('/', Index::index(...));

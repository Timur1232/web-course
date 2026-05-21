<?php
require_once('./init.php');
require_once('./vendor/autoload.php');
spl_autoload_register(Init::autoload(...));

use App\Core\Context\URL;
use App\Core\Test\Test_Driver;
use App\Models\Common_Sql;

Test_Driver::setup([
    Common_Sql::class,
    URL::class,
]);
Test_Driver::run_tests();

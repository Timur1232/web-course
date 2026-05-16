<?php
use Exception;

final class Init {
    // Path to corresponding directory of currently loaded module.
    public static string $current_module_path;

    // Name of the funciton inside `$current_module_path` directory, that should load module.
    // It always has trailing '/' on end.
    public static string $module_file_name = 'init';

    /*
    * @param string[] $file_names
    */
    public static function load(array $file_names): void {
        foreach ($file_names as $file_name) {
            require_once self::$current_module_path.$file_name.'.php';
        }
    }

    public static function autoload(string $class_name): void {
        $file_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name).'.php';
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }

        $splited = explode('\\', $class_name);
        $module_path = implode(DIRECTORY_SEPARATOR, array_slice($splited, 0, count($splited)-1));
        if (file_exists($module_path.'.php')) {
            require_once $module_path.'.php';
            return;
        }

        $init_path = $module_path.DIRECTORY_SEPARATOR.self::$module_file_name.'.php';
        if (!file_exists($init_path)) {
            throw new Exception("Init: Unable to find {$class_name} class :/");
        }
        self::$current_module_path = $module_path.DIRECTORY_SEPARATOR;
        require_once $init_path;
    }

    private function __construct() {}
}


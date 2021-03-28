<?php

namespace IDG2Migration\config;

class GlobalConstant
{
    
    public static string $MAPPING_DIR = IDG_MIGRATION_DIR . '/src/config/mappings/';
    public static string $LOG_DIR = IDG_MIGRATION_DIR . '/src/logs/';
    public static string $WP_SETUP = ABSPATH . '/wp-load.php';
    public static string $WP_TAXONOMY_PATH = ABSPATH . '/wp-admin/includes/taxonomy.php';
    public static string $INPUT_DIR = IDG_MIGRATION_DIR . '/src/config/input_files/';
    public static string $WP_POST_PATH = ABSPATH . '/wp-includes/post.php';
    public static array $PARAMS = [
        'host' => 'localhost',
        'port' => '8888',
        'database' => 'narf',
        'user' => 'narf_readonly',
        'password'=> 'Dr1nkPepsicola#'
    ];

    public function __construct()
    {
    }
}
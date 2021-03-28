<?php
/**
 * Plugin Name:     Idg Migration
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Migration plugin
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     idg-migration
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Idg_Migration
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'IDG_MIGRATION_DIR' ) ) {
	define( 'IDG_MIGRATION_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
}

define( 'IDG_MIGRATION_CLASSES__FILE__', __FILE__ );

require IDG_MIGRATION_DIR . '/vendor/autoload.php';

use IDG2Migration\inc\Activation;

$activation = new Activation();

if ( defined( 'WP_CLI' ) && WP_CLI ) { 
	WP_CLI::add_command( 'idg-migrate', '\IDG2Migration\inc\IdgMigrateContent' );
	WP_CLI::add_command( 'idg-migrate', '\IDG2Migration\inc\IdgMigrateMedia' );
	WP_CLI::add_command( 'idg-migrate', '\IDG2Migration\inc\IdgMigrateTaxonomy' );
}

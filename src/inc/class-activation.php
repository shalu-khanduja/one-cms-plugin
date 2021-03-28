<?php
/**
 * Handles the plugin activation.
 *
 * @package IDG Migration plugin
 */

namespace IDG2Migration\inc;

/**
 * Class which handles the plugin activation.
 */
class Activation {
	
	/**
	 * Add the required hooks.
	 */
	public function __construct() {

		register_activation_hook( IDG_MIGRATION_CLASSES__FILE__, array( $this, 'call_back_create_tables' ) );
		register_activation_hook( IDG_MIGRATION_CLASSES__FILE__, array( $this, 'call_back_insert_data_in_post_mapping' ) );

		register_activation_hook( IDG_MIGRATION_CLASSES__FILE__, array( $this, 'call_back_insert_data_in_term_mapping' ) );
		
	}

	/**
	 * Call back function to creat tables.
	 */
	public function call_back_create_tables() {    
		global $wpdb; 
		$custom_table_prefix = ''; 
		$mg_post_mapping     = $custom_table_prefix . 'migration_post_mapping';  
		$mg_term_mapping     = $custom_table_prefix . 'migration_term_mapping'; 
		$charset_collate     = $wpdb->get_charset_collate();
		$post_query          = "CREATE TABLE IF NOT EXISTS `$mg_post_mapping` (
				`id` int(11) AUTO_INCREMENT,
				`type` varchar(255) NOT NULL,
				`post_id` bigint(20) NOT NULL,
				`old_id_in_onecms` bigint(20) NOT NULL,
				PRIMARY KEY (`id`),
				KEY old_id_in_onecms (old_id_in_onecms),
  				KEY type (type)
			) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $post_query ); // phpcs:ignore
		
		$tax_query = "CREATE TABLE IF NOT EXISTS $mg_term_mapping (
				id int(11) AUTO_INCREMENT,
				taxonomy_type varchar(255) NOT NULL,
				term_id int(11) NOT NULL,
				old_id_in_onecms int(11) NOT NULL,
				PRIMARY KEY (id),
				KEY old_id_in_onecms (old_id_in_onecms),
  				KEY taxonomy_type (taxonomy_type)
			) $charset_collate;";
		dbDelta( $tax_query ); // phpcs:ignore
	} 

	/**
	 * Call back function to insert data into post mapping table.
	 */
	public function call_back_insert_data_in_post_mapping() {

		global $wpdb; 
		
		$wpdb->query( 'TRUNCATE TABLE `migration_post_mapping`' ); // db call ok; no-cache ok.
		
		$wpdb->query( $wpdb->prepare( "INSERT INTO `migration_post_mapping` SELECT NULL, post_type, post_id, meta_value FROM `{$wpdb->prefix}postmeta` as pm LEFT JOIN `{$wpdb->prefix}posts` as p ON p.id = pm.post_id WHERE meta_key = %s  AND post_type IN( %s, %s, %s ) and post_mime_type = %s", 'old_id_in_onecms', 'post', 'product', 'attachment', '' ) ); // db call ok; no-cache ok.
		
		$wpdb->query( $wpdb->prepare( "INSERT INTO `migration_post_mapping` SELECT NULL, 'video', post_id, meta_value FROM `{$wpdb->prefix}postmeta` as pm LEFT JOIN `{$wpdb->prefix}posts` as p ON p.id = pm.post_id WHERE meta_key = %s AND post_type = %s and post_mime_type = %s", 'old_id_in_onecms', 'attachment', 'video/mp4' ) ); // db call ok; no-cache ok.
	}


	/**
	 * Call back function to insert data into taxonomy table.
	 */
	public function call_back_insert_data_in_term_mapping() {

		global $wpdb; 

		$wpdb->query( 'TRUNCATE TABLE `migration_term_mapping`' ); // db call ok; no-cache ok.
		
		$wpdb->query( $wpdb->prepare( "INSERT INTO migration_term_mapping (taxonomy_type, term_id, old_id_in_onecms) SELECT tt.taxonomy, t.term_id, tm.meta_value FROM `{$wpdb->prefix}terms` t LEFT JOIN `{$wpdb->prefix}termmeta` tm ON tm.term_id = t.term_id LEFT JOIN `{$wpdb->prefix}term_taxonomy` tt ON tt.term_id = t.term_id WHERE tm.meta_key = %s AND tm.meta_value != %s;", 'old_id_in_onecms', '' ) ); // db call ok; no-cache ok.
	}
}








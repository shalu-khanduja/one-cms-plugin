<?php
/**
 * This file is using for the all the media migration script
 *
 * @package inc
 */

namespace IDG2Migration\inc;

use IDG2Migration\inc\CommonUtils;

use WP_CLI;

/**
 * This class is used for migrating the attachment content type.
 */
class IdgMigrateMedia extends CommonUtils {

	/**
	 * Function use for the attachment migration script
	 *
	 * @param string $name script name used to run the script.
	 * @param array  $assoc_args is a associated array.
	 * 
	 * Migrates attachment.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<int>]
	 * : Limit to fetch records from source database.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 *
	 * ---
	 * 
	 * [--offset=<int>]
	 * : Offset while fetching records from source database.
	 * Possible value: Any integer greater than or equal to 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--brand-id=<int>]
	 * : Brand id to fetch records from brands.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--is-delta=<string>]
	 * :  fetch records from brands.
	 * possible value: on/off
	 * ---
	 * default: 'off'
	 *  
	 * ---
	 * 
	 * [--sleep-records=<int>]
	 * : Number of records after which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 1000
	 * ---
	 * 
	 * [--sleep-seconds=<int>]
	 * : Number of seconds for which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 60
	 * ---
	 * [--delta-date=<string>]
	 * : When set needs to be a valid date in (Format-YYYY/MM/dd)
	 * ---
	 * Default: Null
	 * ---
	 * 
	 * [--site=<string>]
	 * : Read config from, defaults to default folder.
	 * possible value: defalut
	 * ---
	 * default: 'default'
	 * ---
	 * 
	 * ## EXAMPLES
	 *
	 *  wp idg-migrate attachment --limit=3
	 * The parameter $a is not having any use but is required to have as a first parameter 
	 * to get the remaining parameters in associative mode.
	 */
	public function attachment( $name = 'attachment', $assoc_args ) {
		
		$this->_script_param = $name;
		$this->parse_params( $assoc_args );
		$this->AttachmentMigrations();
	}

	/**
	 * Function use for the attachment_blog migration script
	 *
	 * @param string $name script name used to run the script.
	 * @param array  $assoc_args is a associated array.
	 * 
	 * Migrates attachment_blog.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<int>]
	 * : Limit to fetch records from source database.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 *
	 * ---
	 * 
	 * [--offset=<int>]
	 * : Offset while fetching records from source database.
	 * Possible value: Any integer greater than or equal to 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--brand-id=<int>]
	 * : Brand id to fetch records from brands.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--is-delta=<string>]
	 * :  fetch records from brands.
	 * possible value: on/off
	 * ---
	 * default: 'off'
	 *  
	 * ---
	 * 
	 * [--sleep-records=<int>]
	 * : Number of records after which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 1000
	 * ---
	 * 
	 * [--sleep-seconds=<int>]
	 * : Number of seconds for which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 60
	 * ---
	 * [--delta-date=<string>]
	 * : When set needs to be a valid date in (Format-YYYY/MM/dd)
	 * ---
	 * Default: Null
	 * ---
	 * 
	 * [--site=<string>]
	 * : Read config from, defaults to default folder.
	 * possible value: defalut
	 * ---
	 * default: 'default'
	 * ---
	 * 
	 * ## EXAMPLES
	 *
	 *  wp idg-migrate attachment_blog --limit=3
	 *
	 * The parameter $a is not having any use but is required to have as a first parameter 
	 * to get the remaining parameters in associative mode.
	 */
	public function attachment_blog( $name = 'attachment-blog', $assoc_args ) {
		$this->_script_param = $name;
		$this->parse_params( $assoc_args );
		$this->AttachmentMigrations();
	}

	/**
	 * Function use for the attachment_sponsorship migration script
	 *
	 * @param string $name script name used to run the script.
	 * @param array  $assoc_args is a associated array.
	 * 
	 * Migrates attachment_sponsorship.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<int>]
	 * : Limit to fetch records from source database.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 *
	 * ---
	 * 
	 * [--offset=<int>]
	 * : Offset while fetching records from source database.
	 * Possible value: Any integer greater than or equal to 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--brand-id=<int>]
	 * : Brand id to fetch records from brands.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--is-delta=<string>]
	 * :  fetch records from brands.
	 * possible value: on/off
	 * ---
	 * default: 'off'
	 *  
	 * ---
	 * 
	 * [--sleep-records=<int>]
	 * : Number of records after which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 1000
	 * ---
	 * 
	 * [--sleep-seconds=<int>]
	 * : Number of seconds for which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 60
	 * ---
	 * [--delta-date=<string>]
	 * : When set needs to be a valid date in (Format-YYYY/MM/dd)
	 * ---
	 * Default: Null
	 * ---attachment_blogattachment_blog
	 * 
	 * [--site=<string>]
	 * : Read config from, defaults to default folder.
	 * possible value: defalut
	 * ---
	 * default: 'default'
	 * ---
	 * 
	 * ## EXAMPLES
	 *
	 *  wp idg-migrate attachment_sponsorship --limit=3
	 *
	 * The parameter $a is not having any use but is required to have as a first parameter 
	 * to get the remaining parameters in associative mode.
	 */
	public function attachment_sponsorship( $name = 'attachment-sponsorship', $assoc_args ) {
		
		$this->_script_param = $name;
		$this->parse_params( $assoc_args );
		$this->AttachmentMigrations();
	}

	/**
	 * Function use for the attachment_user migration script
	 *
	 * @param string $name script name used to run the script.
	 * @param array  $assoc_args is a associated array.
	 * 
	 * Migrates attachment_user.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<int>]
	 * : Limit to fetch records from source database.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 *
	 * ---
	 * 
	 * [--offset=<int>]
	 * : Offset while fetching records from source database.
	 * Possible value: Any integer greater than or equal to 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--brand-id=<int>]
	 * : Brand id to fetch records from brands.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--is-delta=<string>]
	 * :  fetch records from brands.
	 * possible value: on/off
	 * ---
	 * default: 'off'
	 *  
	 * ---
	 * 
	 * [--sleep-records=<int>]
	 * : Number of records after which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 1000
	 * ---
	 * 
	 * [--sleep-seconds=<int>]
	 * : Number of seconds for which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 60
	 * ---
	 * [--delta-date=<string>]
	 * : When set needs to be a valid date in (Format-YYYY/MM/dd)
	 * ---
	 * Default: Null
	 * ---attachment_blogattachment_blog
	 * 
	 * [--site=<string>]
	 * : Read config from, defaults to default folder.
	 * possible value: defalut
	 * ---
	 * default: 'default'
	 * ---
	 * 
	 * ## EXAMPLES
	 *
	 *  wp idg-migrate attachment_user --limit=3
	 *
	 * The parameter $a is not having any use but is required to have as a first parameter 
	 * to get the remaining parameters in associative mode.
	 */
	public function attachment_user( $name = 'attachment-user', $assoc_args ) {
		$this->_script_param = $name;
		$this->parse_params( $assoc_args );
		$this->AttachmentMigrations();
	}

	/**
	 * Function use for the video migration script
	 *
	 * @param string $name script name used to run the script.
	 * @param array  $assoc_args is a associated array.
	 * 
	 * Migrates video.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<int>]
	 * : Limit to fetch records from source database.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 *
	 * ---
	 * 
	 * [--offset=<int>]
	 * : Offset while fetching records from source database.
	 * Possible value: Any integer greater than or equal to 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--brand-id=<int>]
	 * : Brand id to fetch records from brands.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 0
	 * ---
	 * 
	 * [--is-delta=<string>]
	 * :  fetch records from brands.
	 * possible value: on/off
	 * ---
	 * default: 'off'
	 *  
	 * ---
	 * 
	 * [--sleep-records=<int>]
	 * : Number of records after which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 1000
	 * ---
	 * 
	 * [--sleep-seconds=<int>]
	 * : Number of seconds for which you want to put execution in sleep mode.
	 * Possible value: Any integer greater than 0
	 * ---
	 * default: 60
	 * ---
	 * [--delta-date=<string>]
	 * : When set needs to be a valid date in (Format-YYYY/MM/dd)
	 * ---
	 * Default: Null
	 * ---
	 * 
	 * [--site=<string>]
	 * : Read config from, defaults to default folder.
	 * possible value: defalut
	 * ---
	 * default: 'default'
	 * ---
	 * 
	 * ## EXAMPLES
	 *
	 *  wp idg-migrate video --limit=3
	 *
	 * The parameter $a is not having any use but is required to have as a first parameter 
	 * to get the remaining parameters in associative mode.
	 */
	public function video( $name = 'video', $assoc_args ) {
		
		$this->_script_param = $name;
		$this->parse_params( $assoc_args );
		$this->AttachmentMigrations();
	}

	/**
	 * This function calls the main attachment migration function.
	 */
	protected function AttachmentMigrations() {
		try {
			$article_migration = new \IDG2Migration\migrations\ArticleMigration( $this->_script_param );
			$article_migration->initMigration( $this->_script_param, $this->_assoc_parm_arr );
		} catch ( Exception $e ) {
			echo esc_html( $e->getMessage() . PHP_EOL );
		}
	}
}

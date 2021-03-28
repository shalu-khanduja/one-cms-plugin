<?php

namespace IDG2Migration\inc;

use IDG2Migration\inc\CommonUtils;

use WP_CLI;

class IdgMigrateContent extends CommonUtils {

    
    /**
     * Migrates Articles.
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
     *---
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article($a = '', $assoc_args) {
        
        $this->_script_param = 'article';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }

    /**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article_video($a = '', $assoc_args) {
        $this->_script_param = 'article-video';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }
    
    /**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article_audio($a = '', $assoc_args) {
        $this->_script_param = 'article-audio';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }
   
    /**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article_bug_updated($a = '', $assoc_args) {
        $this->_script_param = 'article-bug-updated';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }

    /**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article_default($a = '', $assoc_args) {
        $this->_script_param = 'article-default';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }
    
/**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article_external_url($a = '', $assoc_args) {
        $this->_script_param = 'article-external-url';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }
    
    /**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article_slideshow($a = '', $assoc_args) {
        $this->_script_param = 'article-slideshow';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }
  
    /**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article_product_hub($a = '', $assoc_args) {
        $this->_script_param = 'article-product-hub';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }
    

    /**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article_bug_delta($a = '', $assoc_args) {
        $this->_script_param = 'article-bug-delta';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }
  
  /**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */
    //The parameter $a is not having any use but is required to have as a first parameter 
    //to get the remaining parameters in associative mode.
    public function article_display_delta($a = '', $assoc_args) {
        $this->_script_param = 'article-display-delta';
        $this->parse_params($assoc_args);
        $this->ArticleMigrations();
    }

/**
     * Migrates Articles.
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
     *---
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
     * Default: Null
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
     *  wp idg-migrate article --limit=3
     *
     */

    public function articletype($a = '', $assoc_args) {
        $this->_script_param = 'articletype';
        $this->parse_params($assoc_args);
        $articleTypeMigration = new \IDG2Migration\migrations\ArticleTypeMigration($scriptParam,$this->_assoc_parm_arr);
        $articleTypeMigration->initMigration($scriptParam,$this->_assoc_parm_arr);
    }
    


    /**
     * @description: This function calls the main article migration function related to all the article types
     * @types: 'article', 'article-video', 'article-audio', 'article-bug-updated', 'article-default', 'article-external-url', 'article-slideshow', 'article-product-hub', 'article-bug-delta', 'article-display-delta'
     */
    protected function ArticleMigrations(){
        try {
            $articleMigration = new \IDG2Migration\migrations\ArticleMigration($this->_script_param,$this->_assoc_parm_arr);
            $articleMigration->initMigration($this->_script_param,$this->_assoc_parm_arr);
        } catch (Exception $e) {
            echo $e->getMessage().PHP_EOL;
        }
    }

}
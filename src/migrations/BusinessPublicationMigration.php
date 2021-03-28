<?php


namespace IDG2Migration\migrations;

use IDG2Migration\modules\IngesterModule;

class BusinessPublicationMigration
{
    
    /**
     * @var IngesterModule
     */
    private IngesterModule $ingesterModule;

    /**
     * @var array $businessUnit
     */
    private array $businessUnit;

    /**
     * @var array $publication
     */
    private array $publication;

    /**
     * Migration constructor.
     * @param $scriptParam
     */
    public function __construct($scriptParam)
    {
        $this->ingesterModule = new IngesterModule($scriptParam);
        $this->businessUnit = array(
            'term' => array(
                'name' => 'US-Default',
                'slug' => 'us-default',
            ) ,
            'taxonomy' => 'publication',
            'meta' => array(
                'publication_type' => 0,
            )
        );
        $this->publication =  array(
            'term' => array(
                'name' => 'Macworld',
                'slug'=> 'macworld'
            ) ,
            'taxonomy' => 'publication',
            'meta' => array(
                'publication_type' => 1,
                'publication_host' => 'macworld.com',
                'old_id_in_onecms' => '',
            )
        );
    }
    public function initMigration()
    {
        $this->processMigration();
    }

    public function processMigration()
    {
        $this->ingesterModule->setBusinessAndPublicationData($this->businessUnit, $this->publication);
    }
}

<?php


namespace IDG2Migration\migrations;

use IDG2Migration\modules\IngesterModule;

class OriginCMS
{

    /**
     * @var IngesterModule
     */
    private IngesterModule $ingesterModule;
    /**
     * @var array $originCMS
     */
    private array $originCMS;

    /**
     * Migration constructor.
     * @param $scriptParam
     */
    public function __construct($scriptParam)
    {
        $this->ingesterModule = new IngesterModule($scriptParam);
        $this->originCMS = array(
            'term' => array(
                'name' => 'OneCMS',
                'slug' => 'onecms',
            ) ,
            'taxonomy' => 'origin',
        );
    }
    public function initMigration()
    {
        $this->processMigration();
    }

    public function processMigration()
    {
        $this->ingesterModule->setOriginCMSData($this->originCMS);
    }
}

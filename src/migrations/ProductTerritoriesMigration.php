<?php

namespace IDG2Migration\migrations;

use IDG2Migration\modules\IngesterModule;

class ProductTerritoriesMigration
{
    /**
     * @var IngesterModule
     */
    private IngesterModule $ingesterModule;

    /**
     * @var array $usTermObject
     */
    private array $usTermObject;

    /**
     * Migration constructor.
     * @param $scriptParam
     */
    public function __construct($scriptParam)
    {
        $this->ingesterModule = new IngesterModule($scriptParam);
        $this->usTermObject =  array(
            'term' => array(
                'name' => 'United states',
                'slug' => 'us'
            ),
            'taxonomy' => 'territory',
            'country' => 'US',
            'meta' => array(
                'old_id_in_onecms' => '1',
                'default_currency' => 'USD',
            )
        );
    }

    public function initMigration()
    {
        $this->processMigration();
    }

    public function processMigration()
    {
        $this->ingesterModule->insertUsTerritory($this->usTermObject);
    }
}

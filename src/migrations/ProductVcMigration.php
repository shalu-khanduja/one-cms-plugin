<?php

namespace IDG2Migration\migrations;

use IDG2Migration\modules\IngesterModule;

class ProductVcMigration
{
    /**
     * @var IngesterModule
     */
    private IngesterModule $ingesterModule;

    /**
     * @var array $productVc
     */
    private array $productVc;

    /**
     * Migration constructor.
     * @param $scriptParam
     */
    public function __construct($scriptParam)
    {
        $this->ingesterModule = new IngesterModule($scriptParam);
        $this->productVc =  array(
            'term' => array(
                'name' => 'Amazon',
                'slug' => 'amazon'
            ),
            'taxonomy' => 'vendor_code',
            'meta' => array(
                'old_id_in_onecms' => 2,
            )
        );
    }
    public function initMigration()
    {
        $this->processMigration();
    }
    public function processMigration()
    {
        $this->ingesterModule->setProductVcMigrationData($this->productVc);
    }
}

<?php

namespace IDG2Migration\migrations;

use IDG2Migration\helpers\ParserHelper;
use IDG2Migration\modules\FetcherModule;
use IDG2Migration\modules\IngesterModule;
use Monolog\Logger;

class UserMigration
{
    /**
     * @var FetcherModule
     */
    private $fetcherModule;
    /**
     * @var IngesterModule
     */
    private $ingesterModule;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ParserHelper
     */
    private $parserHelper;

    /**
     * Migration constructor.
     */
    public function __construct($scriptParam)
    {
        $this->logger = new Logger('migration_logger');
        $this->fetcherModule = new FetcherModule();
        $this->ingesterModule = new IngesterModule($scriptParam);
        $this->parserHelper = new ParserHelper();
    }

    public function initMigration($scriptParam)
    {
        $xml_obj = $this->parserHelper->parseXml($scriptParam);
        $mappingArr = $this->parserHelper->parseXMLObject($xml_obj);
        $this->processMigration($mappingArr);
    }

    public function processMigration($mapObj)
    {
        foreach ($mapObj as $mapItemKey => $mapItem) {
            $dataFromSource = $this->fetcherModule->fetchDataFromSource($mapItem);
            $this->ingesterModule->userAndUserMetaHandler($dataFromSource, $mapItem);
        }
        echo 'Records has been successfully migrated'.PHP_EOL;
    }
}

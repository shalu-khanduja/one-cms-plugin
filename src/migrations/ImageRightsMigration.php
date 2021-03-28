<?php

namespace IDG2Migration\migrations;

use IDG2Migration\config\GlobalConfig;
use IDG2Migration\helpers\ParserHelper;
use IDG2Migration\modules\FetcherModule;
use IDG2Migration\modules\IngesterModule;
use Monolog\Logger;
use SimpleXMLElement;

class ImageRightsMigration
{
    /**
     * @var string
     */
    private string $scriptParam;
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var FetcherModule
     */
    private FetcherModule $fetcherModule;
    /**
     * @var IngesterModule
     */
    private IngesterModule $ingesterModule;
    /**
     * @var ParserHelper
     */
    private ParserHelper $parserHelper;

    /**
     * Migration constructor.
     * @param $scriptParam
     */
    public function __construct($scriptParam)
    {
        $this->logger = new Logger('migration_logger');
        $this->fetcherModule = new FetcherModule();
        $this->ingesterModule = new IngesterModule($scriptParam);
        $this->parserHelper = new ParserHelper();
        $this->scriptParam = $scriptParam;
    }

    /**
     * @param $scriptParam
     */
    public function initMigration($scriptParam)
    {
        $xml_obj = $this->parserHelper->parseXml($scriptParam);
        $mappingArr = $this->parserHelper->parseXMLObject($xml_obj);
        $this->processMigration($mappingArr);
    }

    /**
     * @param $mapObj
     */
    public function processMigration($mapObj)
    {
        foreach ($mapObj as $mapItemKey => $mapItem) {
            /**
             * condition to handle the mapping which is having meta keys
             */
            if ($mapItem['source']['has_meta'] === 'true') {
                $dataFromSource = $this->fetcherModule->fetchDataFromSource($mapItem);
                $this->ingesterModule->termAndTermMetaHandler($dataFromSource, $mapItem);
            } else {
                // TODO: handle else case for column to column mapping
            }
        }
        echo "Records has been successfully migrated".PHP_EOL;
    }
}

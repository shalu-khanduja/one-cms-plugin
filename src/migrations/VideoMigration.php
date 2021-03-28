<?php

namespace IDG2Migration\migrations;

use IDG2Migration\helpers\ParserHelper;
use IDG2Migration\modules\FetcherModule;
use IDG2Migration\modules\ArticleIngesterModule;
use Monolog\Logger;

class VideoMigration
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
    private ArticleIngesterModule $articleIngesterModule;
    /**
     * @var ParserHelper
     */
    private ParserHelper $parserHelper;
    /**
     * @var $postType
     */
    private string $postType;

    /**
     * Migration constructor.
     * @param $scriptParam
     */
    public function __construct($scriptParam)
    {
        $this->logger = new Logger('migration_logger');
        $this->fetcherModule = new FetcherModule();
        $this->articleIngesterModule = new ArticleIngesterModule($scriptParam);
        $this->parserHelper = new ParserHelper();
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
                $this->articleIngesterModule->articlePostAndItsMetaHandler($dataFromSource, $mapItem);
            }
        }
        echo "Records has been successfully migrated".PHP_EOL;
    }
}

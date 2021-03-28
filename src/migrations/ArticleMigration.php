<?php


namespace IDG2Migration\migrations;

use IDG2Migration\helpers\ParserHelper;
use IDG2Migration\modules\ArticleIngesterModule;
use IDG2Migration\modules\FetcherModule;
use Monolog\Logger;

class ArticleMigration
{
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var FetcherModule
     */
    private FetcherModule $fetcherModule;
    /**
     * @var ParserHelper
     */
    private ParserHelper $parserHelper;
    /**
     * @var ArticleIngesterModule
     */
    private ArticleIngesterModule $articleIngesterModule;

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
     * @param $sleepConst
     */
    public function initMigration($scriptParam, $sleepConst)
    {
        $xml_obj = $this->parserHelper->parseXml($scriptParam);
        $mappingArr = $this->parserHelper->parseXMLObject($xml_obj);
        $this->processMigration($mappingArr, $sleepConst);
    }

    /**
     * @param $mapObj
     * @param $sleepConst
     */
    public function processMigration($mapObj, $sleepConst)
    {
        foreach ($mapObj as $mapItemKey => $mapItem) {
            /**
             * condition to handle the mapping which is having meta keys
             */
            if ($mapItem['source']['has_meta'] === 'true') {
                $dataFromSource = $this->fetcherModule->fetchDataFromSource($mapItem);
                $this->articleIngesterModule->articlePostAndItsMetaHandler($dataFromSource, $mapItem, $sleepConst);
            }
        }
        echo "Records has been successfully migrated".PHP_EOL;
    }
}

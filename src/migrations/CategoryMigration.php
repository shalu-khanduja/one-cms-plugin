<?php

namespace IDG2Migration\migrations;

use IDG2Migration\config\GlobalConfig;
use IDG2Migration\helpers\ParserHelper;
use IDG2Migration\modules\FetcherModule;
use IDG2Migration\modules\IngesterModule;
use Monolog\Logger;

class CategoryMigration
{
    private string $scriptParam;

    private FetcherModule $fetcherModule;

    private IngesterModule $ingesterModule;

    private Logger $logger;

    private ParserHelper $parserHelper;

    /**
     * @var excelLog
     */
    private array $excelLog;

    /**
     * @param mixed $scriptParam
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
     * @param mixed $scriptParam
     */
    public function initMigration($scriptParam)
    {
        $this->excelLog = $this->ingesterModule->parseExcelAndStoreCategory();
        $xmlObj = $this->parserHelper->parseXml($scriptParam);
        $mappingArr = $this->parserHelper->parseXMLObject($xmlObj);
        $this->processMigration($mappingArr);
    }

    /**
     * @param mixed $mapObj
     */
    public function processMigration($mapObj)
    {
        foreach ($mapObj as $mapItemKey => $mapItem) {
            $allTermsData = $this->fetchGoldenIds();
            $mapItem['in_operator_value'] = $allTermsData['allGoldenIds'];
            $dataFromSource = $this->fetcherModule->fetchDataFromSource($mapItem);
            $mapItem['isCategory'] = true;
            $mapItem['termsData'] = $allTermsData['allTermsData'];
            $mapItem['excelLog'] = $this->excelLog;
            $this->ingesterModule->categoryAndMetaHandler($dataFromSource, $mapItem);
        }
        echo 'Records has been successfully migrated'.PHP_EOL;
    }

    /**
     * @return array
     */
    public function fetchGoldenIds()
    {
        global $wpdb;
        $allIds = '';
        $allTerms = [];
        $results = $wpdb->get_results('SELECT meta_value, term_id FROM wp_termmeta WHERE meta_key = "golden_id"');

        foreach ($results as $value) {
            $allIds .= $value->meta_value.',';
            $allTerms[$value->meta_value] = $value->term_id;
        }

        return ['allGoldenIds' => rtrim($allIds, ','), 'allTermsData' => $allTerms];
    }
}

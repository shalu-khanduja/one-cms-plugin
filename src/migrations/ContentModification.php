<?php


namespace IDG2Migration\migrations;

use IDG2Migration\helpers\DataFilter;
use IDG2Migration\modules\ContentModificationModule;
use IDG2Migration\config\GlobalConfig;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ContentModification
{
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var ContentModificationModule
     */
    private ContentModificationModule $contentModule;
    /**
     * @var DataFilter
     */
    private DataFilter $dataFilter;
    private int $totalUpdateCount;
    private int $totalErrorCount;
    private array $transformHistory;

    /**
     * ContentModification constructor.
     * @param $scriptParam
     */
    public function __construct($scriptParam)
    {
        $this->dataFilter = new DataFilter();
        $this->logger = new Logger('migration_logger');
        $logFile = !empty($scriptParam) ? trim($scriptParam) : 'general';
        $this->logger->pushHandler(
            new StreamHandler(
                $this->dataFilter->getDirectory($scriptParam).$logFile.time().'.log',
                Logger::INFO
            )
        );
        $this->contentModule = new ContentModificationModule();
        $this->totalUpdateCount = 0;
        $this->totalErrorCount = 0;
    }

    /**
     * @param $queryParam
     */

    public function initMigration($queryParam)
    {
        $idsForInClause = '';
        $result = $this->contentModule->getPosts($queryParam);
        $this->logger->info(
            sprintf('Total records lineup for transformation {%s} ', count($result))
        );
        if (count($result) > 0) {
            // first foreach to generate string of ids used further in IN cause
            foreach ($result as $item) {
                $idsForInClause .=
                    $idsForInClause === '' ?
                        $item['postmeta']['old_id_in_onecms'][0] :
                        ', ' . $item['postmeta']['old_id_in_onecms'][0];
            }
            // fetch products details, which are associated with this article
            $productArray = $this->contentModule->getProductDetailsFromSource($idsForInClause);

            // second foreach
            foreach ($result as $item) {
                // initiate call for transform
                $this->transformHistory = GlobalConfig::$TRANSFORM_HISTORY;
                $this->transformIntoBlocks($item, $productArray);
            }
        } else {
            $this->logger->info('No records for transformation.');
        }
        $this->logger->info(
            sprintf('Total records updated {%s}', $this->totalUpdateCount)
        );
        $this->logger->info(
            sprintf('Total records having error {%s}', $this->totalErrorCount)
        );
        echo "Records has been successfully updated".PHP_EOL;
    }

    /**
     * @param $manString
     * @param $productArray
     * @return string
     */
    public function transformProductReviewBlock($manString, $productArray): string
    {
        // TODO: add function call to process review
        $patternSideBar='/data-productid="([^"]+)/s';
        preg_match_all($patternSideBar, $manString, $matches);
        $fnVal=array_unique($matches[1]);
        return '<!-- wp:idg-base-theme/review-block {"primaryProductId":'.$fnVal[0].'} -->
            <div class="review-columns"><div class="review-column"><h3 class="review-subTitle">Pros</h3><ul class="pros review-list"><li></li></ul></div><div class="review-column"><h3 class="review-subTitle">Cons</h3><ul class="cons review-list"><li></li></ul></div></div><h3 class="review-subTitle review-subTitle--borderTop">Our Verdict</h3><p class="verdict"></p>
            <!-- /wp:idg-base-theme/review-block -->';
    }

    /**
     * @param $stringToManipulate
     * @param $productArray
     */
    public function transformToRemove($stringToManipulate, $productArray)
    {
        // This is the case where we need to return nothing.
        return;
    }

    /**
     * @param $stringToManipulate
     * @param $productArray
     *
     * @return mixed
     */
    public function transformInToProductChart($stringToManipulate, $productArray)
    {
        preg_match_all('/<section class="product product-chart[^>]*"><a class="chart-quick-hit-label".*?<\/section>/s',
            $stringToManipulate,
            $quickChart,
            PREG_UNMATCHED_AS_NULL,
            0
        );

        if (!empty($quickChart[0][0])) {
            $this->transformHistory['IsProductChartTransformed'] = true;
            $this->logger->info('Quick Chart detected and removed');

            return;
        }

        return $this->contentModule->getProductChartBlock(
            $stringToManipulate,
            $productArray,
            $this->logger,
            $this->transformHistory
        );
    }

    /**
     * @param $htmlContent
     * @param $productArray
     */
    public function transformProductLinks($htmlContent, $productArray)
    {
        return $this->contentModule->productLinkBlock(
            $htmlContent,
            $productArray,
            $this->logger,
            $this->transformHistory
        );
    }

    /**
     * @param $stringToManipulate
     * @param $productArray
     *
     * @return mixed
     */
    public function transformInToSidebar($stringToManipulate, $productArray)
    {
        return $this->contentModule->sideBarBlock(
            $stringToManipulate,
            $productArray,
            $this->logger,
            $this->transformHistory
        );
    }

    /**
     * @param $manString
     * @return string|string[]|null
     */
    public function transformPagination($manString)
    {
        $newline = '/<\/section>[\n|\s]*<section class="page">/';
        $this->transformHistory['IsPaginationTransformed'] = true;
        return preg_replace($newline, '<!-- wp:nextpage --><!--nextpage--><!-- /wp:nextpage -->', $manString);
    }

    /**
     * @param $manString
     * @return mixed
     */
    public function transformVideo($manString)
    {
        return $this->contentModule->videoModificationBlock(
            $manString,
            $this->logger,
            $this->transformHistory
        );
    }

    /**
     * @param $item
     * @param $productArray
     * @return string
     */
    public function transformIntoBlocks($item, $productArray)
    {
        $this->logger->info(
            sprintf(
                '----Start transformation of {%d} {%s} one cms id {%d}----------',
                $item['post']->ID,
                $item['post']->post_title,
                $item['postmeta']['old_id_in_onecms'][0]
            )
        );
        $strWithRemovedEnd = $item['post']->post_content;
        $massage=[];
        $wpHtmlBlockStart='<!-- wp:html -->';
        $wpHtmlBlockEnd='<!-- /wp:html -->';
        $muTitleStart='<!-- wp:bigbite/multi-title -->';
        $muTitleEnd='<!-- /wp:bigbite/multi-title -->';

        $massage[]=array(
            'pattern'=>'/<aside class=".*?product-sidebar.*?">.*?<\/aside>/s',
            'callback'=>'transformInToSidebar'
        );
        /*$massage[]=array(
            'pattern'=>'/<aside class="product product-sidebar enhanced[^>]*">/s',
            'callback'=>'transformInToSidebar'
        );*/
        $massage[]=array(
            'pattern'=>'/<nav class=".*?product-list.*?">.*?<\/nav>/s',
            'callback' => 'transformToRemove'
        );
        $massage[]=array(
            'pattern'=>'/<section class="product product-chart[^>]*">.*?<\/section>/s',
            'callback' => 'transformInToProductChart'
        );
        $massage[]=array(
            'pattern'=>'/<\/section>[\n|\s]*<section class="page">/s',
            'callback'=>'transformPagination'
        );
        $massage[]=array(
            'pattern'=>'/<figure class="(large|small|medium)">[\n|\s]*<div class="embed-wrapper">[\n|\s]*<div class="embed-container">[\n|\s]*<video.*?<\/figure>/s',
            'callback'=>'transformVideo'
        );
        $initStart = 0;
        preg_match(
            '/<!-- \/wp:bigbite\/multi-title -->/s',
            $strWithRemovedEnd,
            $multiLineMatches,
            PREG_OFFSET_CAPTURE,
            0
        );
        if (count($multiLineMatches) > 0) {
            $initStart = $multiLineMatches[0][1]+strlen($multiLineMatches[0][0]);
        }
        $needleHayStack=[];
        $index = 0;
        $newStr=substr($strWithRemovedEnd, 0, $initStart);

        foreach ($massage as $pt) {
            $matches=[];
            $start = $initStart;
            $callBack=false;
            if (isset($pt['callback']) && $pt['callback']!=='') {
                $callBack = true;
            }
            preg_match_all($pt['pattern'], $strWithRemovedEnd, $matches, PREG_OFFSET_CAPTURE, 0);

            foreach ($matches[0] as $k => $m) {
                $index = $m[1];
                $substring = $m[0];
                $rep='';
                if ($callBack) {
                    $rep = call_user_func_array(array($this, $pt['callback']), array($substring, $productArray));
                }
                $needleHayStack[$index]=array(
                    'start_pos'=>$index,
                    'end_pos'=>$index+strlen($substring),
                    'non_match_wrap_start'=>$wpHtmlBlockStart,
                    'non_match_wrap_end'=>$wpHtmlBlockEnd,
                    'substring'=>$substring,
                    'replacement'=>$rep,
                );
            }
        }
        ksort($needleHayStack);
        foreach ($needleHayStack as $index => $value) {
            $substring=$value['substring'];
            $replacement=$value['replacement'];
            $newStr.=$value['non_match_wrap_start'];
            $newStr.=substr($strWithRemovedEnd, $start, ($index-$start));
            $newStr.=$value['non_match_wrap_end'];
            $newStr.=$replacement;
            $start = $index+strlen($substring);
        }
        if ($start < strlen($strWithRemovedEnd)) {
            $newStr.=$wpHtmlBlockStart;
            $newStr.=substr($strWithRemovedEnd, $start, strlen($strWithRemovedEnd));
            $newStr.=$wpHtmlBlockEnd;
        }
        $cleanStr = preg_replace('/<!-- wp:html -->[\s|\n]*<!-- \/wp:html -->/', '', $newStr);
        $finalContent = $this->transformProductLinks($cleanStr, $productArray);
        $this->logger->info(
            sprintf('Record for update {%d} {%s} ', $item['post']->ID, json_encode($this->transformHistory))
        );
        $updateResult = $this->contentModule->saveUpdatedContent($item['post']->ID, $finalContent);
        if ($updateResult > 0) {
            $this->contentModule->savePostMetaContent($item['post']->ID, 'is_transformed', 1, $this->logger);
            $this->contentModule->savePostMetaContent($item['post']->ID, 'transform_history', $this->transformHistory, $this->logger);
            $this->totalUpdateCount++;
            $this->logger->info(sprintf('Result {%s}', json_encode($updateResult)));
        } else {
            $this->contentModule->savePostMetaContent($item['post']->ID, 'is_transformed', 0, $this->logger);
            $this->contentModule->savePostMetaContent($item['post']->ID, 'transform_history', $this->transformHistory, $this->logger);
            $this->totalErrorCount++;
            $this->logger->error(sprintf('Result {%s}', json_encode($updateResult)));
        }
        $this->logger->info('----End of migration----------');
    }
}

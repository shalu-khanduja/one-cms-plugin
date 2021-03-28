<?php


namespace IDG2Migration\migrations;

use IDG2Migration\config\GlobalConstant;
use IDG2Migration\helpers\DataFilter;
use IDG2Migration\modules\FilterContentModule;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class FilterContent
{
    /**
     * @var FilterContentModule
     */
    private FilterContentModule $filterContentModule;
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var DataFilter
     */
    private DataFilter $dataFilter;
    private int $totalRecordFiltered = 0;
    private int $totalRecordNotFiltered = 0;
    private int $totalErrorCount = 0;

    /**
     * Filter Content constructor.
     * @param $scriptParam
     */
    public function __construct($scriptParam)
    {
        $this->filterContentModule = new FilterContentModule();
        $this->dataFilter = new DataFilter();
        $this->logger = new Logger('migration_logger');
        $logFile = !empty($scriptParam) ? trim($scriptParam) : 'general';
        $this->logger->pushHandler(
            new StreamHandler(
                $this->dataFilter->getDirectory($logFile).$logFile.time().'.log',
                Logger::INFO
            )
        );
    }

    /**
     * @param $queryLimit
     * @param $queryOffset
     */
    public function initFilter($queryLimit, $queryOffset)
    {
        $result = $this->filterContentModule->getAllPostContent($queryLimit, $queryOffset);
        $this->logger->info(
            sprintf('Total records lineup for filter {%s}', count($result))
        );
        if (count($result) > 0) {
            foreach ($result as $key => $item) {
                $this->logger->info(
                    sprintf('----Start filter of post {%d}----------', $item->id)
                );
                $removedWPHtmlStart = str_replace('<!-- wp:html -->', '', trim($item->post_content));
                $removedWPHtmlEnd = str_replace('<!-- /wp:html -->', '', $removedWPHtmlStart);
                $extra = '<!-- wp:bigbite/multi-title --><section class="wp-block-bigbite-multi-title"><div class="container"></div></section><!-- /wp:bigbite/multi-title -->';
                $initStart = 0;
                preg_match(
                    '/<!-- \/wp:bigbite\/multi-title -->/s',
                    $removedWPHtmlEnd,
                    $multiLineMatches,
                    PREG_OFFSET_CAPTURE,
                    0
                );
                if (count($multiLineMatches) > 0) {
                    $initStart = $multiLineMatches[0][1]+strlen($multiLineMatches[0][0]);
                }

                $innerHtmlString = substr($removedWPHtmlEnd, $initStart, strlen($removedWPHtmlEnd));
                $filteredWithStartAndEnd = $this->dataFilter->removeStartAndEndPart($innerHtmlString);
                $finalString = $extra.$filteredWithStartAndEnd;
                $saveResult = $this->filterContentModule->saveUpdatedContent($item->id, $finalString);
                if ($saveResult > 0) {
                    $this->logger->info(
                        sprintf('----Record updated successfully post {%d}----------', json_encode($saveResult))
                    );
                    $this->totalRecordFiltered++;
                } else {
                    $this->logger->error(
                        sprintf('----Failed for post {%d}----------', json_encode($saveResult))
                    );
                    $this->totalErrorCount++;
                }
                $this->logger->info('----End of filter----------');
            }
        }
        $this->logger->info(
            sprintf('Total records filtered {%s}', $this->totalRecordFiltered)
        );
        $this->logger->info(
            sprintf('Total error records {%s}', $this->totalErrorCount)
        );
        echo "Records has been successfully filtered.".PHP_EOL;
    }

    /**
     * @param array $data
     *
     */
    public function initRemoveInactiveTag($data)
    {
        $failedCount = 0;
        $deletedCount = 0;

        if ($data['name'] == 'post_tag') {
            $inactiveTags = $this->filterContentModule->getInactivePostTags($data['queryLimit'], $data['queryOffset']);
        } elseif ($data['name'] == 'image-tag') {
            $inactiveTags = $this->filterContentModule->getInactiveImageTags($data['queryLimit'], $data['queryOffset']);
        }

        $this->logger->info(
            sprintf('Total records lineup for Removing Inactive Tags {%s}', count($inactiveTags))
        );
        foreach ($inactiveTags as $value) {
            $sourceTagId = $value['id'];
            $newTagId = $this->filterContentModule->getNewReferenceIdByOld(
                $data['taxonomy'],
                $sourceTagId,
                'old_id_in_onecms'
            );
            if ($newTagId!=0 || $newTagId!='ref_error') {
                $result = wp_delete_term($newTagId, $data['taxonomy']);
                if (is_wp_error($result)) {
                    $failedCount++;
                    $this->logger->error(
                        sprintf(
                            '{%d}-{%d} {%d} term id deletion failed for taxonomy {%s}. reason - {%s}}',
                            $sourceTagId,
                            $newTagId,
                            $newTagId,
                            $data['taxonomy'],
                            $result->get_error_message()
                        )
                    );
                } else {
                    $deletedCount++;
                    $this->logger->info(
                        sprintf(
                            '{%d}-{%d} {%d} term id deletion for taxonomy {%s} successful.}',
                            $sourceTagId,
                            $newTagId,
                            $newTagId,
                            $data['taxonomy']
                        )
                    );
                }
            } else {
                $failedCount++;
                $this->logger->error(
                    sprintf(
                        '{%d} term id not found in wordpress for taxonomy {%s}.}',
                        $sourceTagId,
                        $data['taxonomy']
                    )
                );
            }
        }

        $this->logger->info(
            sprintf('Total records deleted {%s}', $deletedCount)
        );
        $this->logger->info(
            sprintf('Total error records {%s}', $failedCount)
        );

        echo "Script execution completed.".PHP_EOL;
    }

    /**
    * @param $queryLimit
    * @param $queryOffset
    */
    public function initHandleReviews($queryLimit, $queryOffset)
    {
        $reviewResult = $this->filterContentModule->getSingleAndMultiReviewsFromSource();
        $articleList = implode(",", array_filter(array_keys($reviewResult)));
        $result = $this->filterContentModule->getAllUntransformedPostContent($queryLimit, $queryOffset, $articleList);

        $this->logger->info(
            sprintf('Total records lineup for review transform {%s}', count($result))
        );
        if (count($result) > 0) {
            foreach ($result as $key => $item) {
                $this->logger->info(
                    sprintf('----Start review transform of post {%d}----------', $item->id)
                );
                if (count($reviewResult[$item->id]) === 1) {
                    // case of Single product primary review
                    $this->transformReviewBlock($item, $reviewResult, 'yes');
                } elseif (count($reviewResult[$item->id]) > 1) {
                    // case of Primary review of more than one product
                    $this->transformReviewBlock($item, $reviewResult, 'no');
                } else {
                    $this->totalRecordNotFiltered++;
                    $this->logger->info(
                        sprintf('----Not eligible for transform {%s}', $item->id)
                    );
                }
                $this->logger->info('----End of review transform----------');
            }
        }
        $this->logger->info(
            sprintf('Total records transform {%s}', $this->totalRecordFiltered)
        );
        $this->logger->info(
            sprintf('Total records not eligible for transform {%s}', $this->totalRecordNotFiltered)
        );
        $this->logger->info(
            sprintf('Total error records {%s}', $this->totalErrorCount)
        );
        echo "Records has been successfully filtered.".PHP_EOL;
    }

    /**
     * @param $item
     * @param $reviewResult
     * @param string $forSingleReview
     */
    public function transformReviewBlock($item, $reviewResult, $forSingleReview = 'no')
    {
        /**
         * generate review block
         */
        $reviewBlock = $this->filterContentModule->generateProductReviewBlock(
            $item->id,
            $reviewResult[$item->id]
        );
        $this->logger->info(
            sprintf(
                '----new review block post {%s}----------',
                $reviewBlock
            )
        );

        /**
         * find the position to add review block
         * add block at correct position
         */
        $updatedContent = $this->filterContentModule->appendBlockAtStart($reviewBlock, $item->post_content);

        /**
         * update updated content in DB
         */
        $saveResult = $this->filterContentModule->saveUpdatedContent($item->id, $updatedContent);
        if ($saveResult > 0) {
            $this->logger->info(
                sprintf(
                    '----Record updated successfully post {%d}----------',
                    json_encode($saveResult)
                )
            );

            /**
             * update transform flag in postmeta for post/article
             */
            $transResult = $this->filterContentModule->updateTransformedMetaInfo($item->id, 2);
            $this->logger->info(
                sprintf(
                    '----set the transform flag {%s}----------',
                    json_encode($transResult)
                )
            );

            /**
             * update 'reviews' meta value in postmeta for post
             * update 'reviews' meta value in postmeta for product
             * update 'transform_history' meta value in postmeta for post
             */
            $rating = $reviewResult[$item->id][0]['rating'] != ''
                ? $reviewResult[$item->id][0]['rating'] : 0;
            $editorsChoice = !$reviewResult[$item->id][0]['is_awarded'] === null;
            $productId = $reviewResult[$item->id][0]['newProductId'];
            $this->filterContentModule->addReviewMetaInfo(
                $item->id,
                $productId,
                $editorsChoice,
                $rating,
                $item->post_date,
                $this->logger,
                $forSingleReview
            );
            $this->totalRecordFiltered++;
        } else {
            $this->logger->error(
                sprintf('----Failed for post {%d}----------', json_encode($saveResult))
            );
            $this->totalErrorCount++;
        }
    }

    /**
     * @param $queryLimit
     * @param $queryOffset
     */
    public function setIsTransformed($queryLimit, $queryOffset)
    {
        $result = $this->filterContentModule->getPostContentWithoutIsTransformed(
            $queryLimit,
            $queryOffset
        );
        $this->logger->info(
            sprintf('Total records lineup for update {%s}', count($result))
        );
        if (count($result) > 0) {
            foreach ($result as $key => $item) {
                $this->logger->info(
                    sprintf('----Start update of postmeta {%d}----------', $item->ID)
                );
                $updateMeta = update_post_meta($item->ID, 'is_transformed', 0, false);
                if (!is_wp_error($updateMeta)) {
                    $this->totalRecordFiltered++;
                    $this->logger->info(
                        sprintf(
                            'Add postmeta -post ID- {%d} -meta key- {%s} -keyvalue- {%s}',
                            $item->ID,
                            'is_transformed',
                            0
                        )
                    );
                } else {
                    $this->logger->error(
                        sprintf(
                            '----Failed for post {%d}----------',
                            $item->ID
                        )
                    );
                    $this->totalErrorCount++;
                }
                $this->logger->info('----End of update----------');
            }
        }
        $this->logger->info(
            sprintf('Total records updated {%s}', $this->totalRecordFiltered)
        );
        $this->logger->info(
            sprintf('Total error records {%s}', $this->totalErrorCount)
        );
        echo "Records has been successfully updated." . PHP_EOL;
    }

    /**
     * @param $queryLimit
     * @param $queryOffset
     */
    public function initHandleReviewsChats($queryLimit, $queryOffset)
    {
        require_once GlobalConstant::$WP_SETUP;
        require_once GlobalConstant::$WP_TAXONOMY_PATH;
        $reviewResult = $this->filterContentModule->getReviewChartFromSource();
        $articleList = implode(",", array_filter(array_keys($reviewResult)));
        $result = $this->filterContentModule->getAllUntransformedPostContent($queryLimit, $queryOffset, $articleList);
        $this->logger->info(
            sprintf('Total records lineup for review transform {%s}', count($result))
        );
        if (count($result) > 0) {
            foreach ($result as $key => $item) {
                $this->logger->info(
                    sprintf('----Start review transform of post {%d}----------', $item->id)
                );
                if (count($reviewResult[$item->id]) > 0) {
                    // generate chart block
                    $chartBlock = $this->filterContentModule->generateProductChartBlock(
                        $item->id,
                        $reviewResult[$item->id],
                        $this->logger
                    );
                    $this->logger->info(sprintf('----generate block for product chart {%s}----------', $chartBlock));
                    // append new chart block to post content
                    $item->post_content = $this->dataFilter->sanitizeJSON($item->post_content);
                    $updatedContent = $item->post_content.$chartBlock;
                    // update new content in DB
                    $result = $this->filterContentModule->saveUpdatedContent($item->id, $updatedContent);
                    if (is_wp_error($result)) {
                        $this->logger->error(
                            sprintf(' --{%d}-- '.$result->get_error_message(), $item->id)
                        );
                        $this->totalErrorCount++;
                    } else {
                        $this->logger->info(sprintf('----record updated successfully for Post -{%d}', $item->id));
                        /**
                         * update transform flag in postmeta for post/article
                         */
                        $transResult = $this->filterContentModule->updateTransformedMetaInfo($item->id, 2);
                        $this->logger->info(
                            sprintf(
                                '----set the transform flag {%s}----------',
                                json_encode($transResult)
                            )
                        );
                        $this->filterContentModule->addTransformHistory(
                            $item->id,
                            'IsReviewChartTransformed',
                            $this->logger
                        );
                        $this->totalRecordFiltered++;
                    }
                } else {
                    $this->totalRecordNotFiltered++;
                    $this->logger->info(
                        sprintf('----Not eligible for transform {%s}', $item->id)
                    );
                }
                $this->logger->info('----End of review transform----------');
            }
        }
        $this->logger->info(
            sprintf('Total records transform {%s}', $this->totalRecordFiltered)
        );
        $this->logger->info(
            sprintf('Total records not eligible for transform {%s}', $this->totalRecordNotFiltered)
        );
        $this->logger->info(
            sprintf('Total error records {%s}', $this->totalErrorCount)
        );
        echo "Records has been successfully filtered.".PHP_EOL;
    }

    public function deleteSpecificPosts()
    {
        $deletedCount = 0;
        $postIds =  $this->filterContentModule->getPostIdsToDelete();
        $this->logger->info(
            sprintf('Total records lineup for deleting post {%d}', count($postIds))
        );
        foreach($postIds as $postId) {
            wp_delete_post($postId->post_id, true);
            $deletedCount++;
            $this->logger->info(
                sprintf(
                    '{%d}-{%d} {%d} post id deletion in wordpress successful.',
                    $postId->old_id_in_onecms,
                    $postId->post_id,
                    $postId->post_id
                )
            );
        }
       $this->filterContentModule->deletePostsFromMappingTable($this->logger);
       $this->logger->info(
            sprintf('Total posts deleted in wordpress {%d}', $deletedCount)
        );

        echo "Records deleted successfully.".PHP_EOL;
    }

    /**
     * Clean access articles from DB
     * For cleaning we are updating it's status as trash
     */
    public function cleanAccessArticles()
    {
        $postArray =  $this->filterContentModule->getAllOneCMSIds();
        $idsForInClause = '';
        $totalUpdate = 0;
        $totalError = 0;
        if (count($postArray) > 0) {
            foreach ($postArray as $item) {
                $idsForInClause .= $idsForInClause === '' ? $item->meta_value : ', ' . $item->meta_value;
            }
        } else {
            $this->logger->info(
                sprintf('Total records lineup from destination DB {%s}', count($postArray))
            );
        }
        if ($idsForInClause !== '') {
            $accessArticleArr = $this->filterContentModule->getAccessArticles($idsForInClause);
            $this->logger->info(
                sprintf('Total records lineup for update {%s}', count($accessArticleArr))
            );
            if (count($accessArticleArr) > 0) {
                $this->logger->info(
                    sprintf('Access records {%s}', json_encode($accessArticleArr))
                );
                foreach ($accessArticleArr as $itm) {
                    $newPostId = $this->filterContentModule->getNewPostIdByOldOneCMSId($itm['id']);
                    if ($newPostId > 0) {
                        $this->logger->info(
                            sprintf(
                                '----Start update of post {%s} with one cms id {%d}----------',
                                $newPostId,
                                $itm['id']
                            )
                        );
                        // update code will goes here
                        $updateResult = $this->filterContentModule->updateArticleStatus($newPostId);
                        if ($updateResult === 0) {
                            $this->logger->error('Error in update');
                            $totalError++;
                        } else {
                            $this->logger->info('Record has been successfully marked as trash');
                            $totalUpdate++;
                        }
                        $this->logger->info('----End of update----------');
                    }
                } // end of foreach
                $this->logger->info(
                    sprintf('Total records updated {%s}', $totalUpdate)
                );
                $this->logger->info(
                    sprintf('Total error records {%s}', $totalError)
                );
            } else {
                $this->logger->info('No access article present');
            }
        }
        echo "Records cleaned successfully.".PHP_EOL;
    }


    /**
     * @param string $type
     *
     * Clean access products, video from DB
     * For cleaning we are updating it's status as trash
     */
    public function cleanAccessType($type = 'product')
    {
        $typeArray =  $this->filterContentModule->getAllOneCMSIds($type);
        $idsForInClause = '';
        $totalUpdate = 0;
        $totalError = 0;
        if (count($typeArray) > 0) {
            foreach ($typeArray as $item) {
                $idsForInClause .= $idsForInClause === '' ? $item->meta_value : ', ' . $item->meta_value;
            }
        } else {
            $this->logger->info(
                sprintf('Total records lineup for product cleanup {%s}', count($typeArray))
            );
        }

        if ($idsForInClause !== '') {
            if ($type == 'product') {
                $accessArr = $this->filterContentModule->getAccessProducts($idsForInClause);
            } elseif ($type == 'video') {
                $accessArr = $this->filterContentModule->getAccessVideos($idsForInClause);
            } elseif ($type == 'attachment') {
                $accessArr = $this->filterContentModule->getAccessAttachments($idsForInClause);
            }
            $this->logger->info(
                sprintf('Total records lineup for update {%s}', count($accessArr))
            );
            if (count($accessArr) > 0) {
                $this->logger->info(
                    sprintf('Access records {%s}', json_encode($accessArr))
                );

                foreach ($accessArr as $itm) {
                    $newPostId = $this->filterContentModule->getNewPostIdByOldOneCMSId($itm['id'], $type);
                    if ($newPostId > 0) {
                        $this->logger->info(
                            sprintf(
                                '----Start update of post {%s} with one cms id {%d}----------',
                                $newPostId,
                                $itm['id']
                            )
                        );
                        // update code will goes here
                        $updateResult = $this->filterContentModule->updateArticleStatus($newPostId);
                        if ($updateResult === 0) {
                            $this->logger->error('Error in update');
                            $totalError++;
                        } else {
                            $this->logger->info('Record has been successfully marked as trash');
                            $totalUpdate++;
                        }
                        $this->logger->info('----End of update----------');
                    }
                } // end of foreach
                $this->logger->info(
                    sprintf('Total records updated {%s}', $totalUpdate)
                );
                $this->logger->info(
                    sprintf('Total error records {%s}', $totalError)
                );
            } else {
                $this->logger->info('No access product present');
            }
        }
        echo "Records cleaned successfully.".PHP_EOL;
    }

    /**
     * Fun for updating byline values in meta which are missed during actual migration
     * due to some limitation at source end
     */
    public function initByLineDelta()
    {
        $totalUpdate = 0;
        $totalExistingRecord = 0;
        // fetch records from OneCMS
        $recordArray = $this->filterContentModule->getDeltaBylineFromOneCMS($this->logger);
        // update records in WP with byline info
        if (count($recordArray) > 0) {
            foreach ($recordArray as $itm) {
                // as per the discussion we need to update byline value what we are getting from source
                if ($itm['newArticleId'] > 0) {
                    $this->logger->info(
                        sprintf(
                            '----Start update of {%s} one cms id {%d}----------',
                            $itm['newArticleId'],
                            $itm['article_id']
                        )
                    );
                    $this->filterContentModule->updateArticleByLine($itm['newArticleId'], $itm['name'], $this->logger);
                    $this->logger->info('----End of migration----------');
                    $totalUpdate++;
                } else {
                    $this->logger->info(
                        sprintf(
                            '----By line already present or post is not present for post id {%d}----------',
                            $itm['newArticleId']
                        )
                    );
                    $totalExistingRecord++;
                }
            }
        }
        $this->logger->info(
            sprintf('Total records updated {%s}', $totalUpdate)
        );
        $this->logger->info(
            sprintf('Total records already present {%s}', $totalExistingRecord)
        );
        echo "Records updated successfully.".PHP_EOL;
    }
}

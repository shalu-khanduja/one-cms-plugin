<?php


namespace IDG2Migration\modules;

use IDG2Migration\config\GlobalConfig;
use IDG2Migration\config\GlobalConstant;
use IDG2Migration\helpers\DataFilter;
use IDG2Migration\repository\IngesterRepository;
use IDG2Migration\util\ContentTypeUtil;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ArticleIngesterModule
{
    /**
     * @var array
     */
    private array $referenceColumns = ['post_author', '_thumbnail_id'];
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var IngesterRepository
     */
    private IngesterRepository $ingesterRepository;
    /**
     * @var DataFilter
     */
    private DataFilter $dataFilter;

    /**
     * IngesterModule constructor.
     *
     * @param string $logFileName
     */

    private int $totalInsertCount = 0;
    private int $totalUpdateCount = 0;
    private int $totalErrorCount = 0;

    /**
     * ArticleIngesterModule constructor.
     * @param string $logFileName
     */
    public function __construct($logFileName = '')
    {
        $this->dataFilter = new DataFilter();
        $this->logger = new Logger('migration_logger');
        $logFile = !empty($logFileName) ? trim($logFileName) : 'general';
        $this->logger->pushHandler(
            new StreamHandler(
                $this->dataFilter->getDirectory($logFile).$logFile.time().'.log',
                Logger::INFO
            )
        );
        $this->ingesterRepository = new IngesterRepository();
    }

    /**
     * @param $sourceDataObject
     * @param $mapItem
     * @param $sleepConst
     */
    public function articlePostAndItsMetaHandler($sourceDataObject, $mapItem, $sleepConst)
    {
        require_once GlobalConstant::$WP_SETUP;
        //This is used to suppress wordpress default filters while post content saving.
        // Iframes are generally not saved while wp_insert_post.
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        //Log Executed SQL Query before operation start in the log
        $this->ingesterRepository->logExecutedSqlQuery($this->logger);
        if (count($sourceDataObject)) {
            $this->logger->info(
                sprintf('Total records lineup for migration {%s}', count($sourceDataObject))
            );
            /**
             * assign map source cols, just to save length of line during multiple use
             */
            $mapSourceCols = $mapItem['source']['cols'];
            /**
             * assign map destination cols, just to save length of line during multiple use
             */
            $metaColObj = $mapItem['destination']['cols'];
            foreach ($sourceDataObject as $item) {
                $postArray = [];
                $postMetaArray = [];
                $postTermRelation = [];
                $updateTitle = false;
                foreach ($item as $key => $ite) {
                    $is_key_present = in_array($key, str_replace('.', '_', $mapSourceCols));
                    if (!$is_key_present) {
                        /**
                         * special case where column alias 'as' has been added in column
                         */
                        if (array_key_exists($key, GlobalConfig::$ALISE_SPECIAL_KEYS)) {
                            $is_key_present = true;
                        }
                    }
                    if ($is_key_present) {
                        $keyIndex = array_search($key, str_replace('.', '_', $mapSourceCols));
                        if ($keyIndex === false) {
                            $keyIndex = array_search(GlobalConfig::$ALISE_SPECIAL_KEYS[$key], $mapSourceCols);
                        }
                        if (count($metaColObj[$keyIndex]) > 0) {
                            if ($metaColObj[$keyIndex]['is_meta'] === false) {
                                $postArray[$metaColObj[$keyIndex]['name']] =
                                    $this->nonMetaTypeNodeHandler($ite, $mapItem, $keyIndex);
                            } elseif ($metaColObj[$keyIndex]['is_meta'] === true
                                && $metaColObj[$keyIndex]['wp_term_relationships'] !== 1) {
                                if ($metaColObj[$keyIndex]['multi_title'] === 1) {
                                    $postMetaArray['multi_title'][$metaColObj[$keyIndex]['name']] =
                                        $this->metaTypeNodeHandler($ite, $mapItem, $keyIndex);
                                } elseif ($metaColObj[$keyIndex]['region_info'] === 1) {
                                    $postMetaArray['region_info'][$metaColObj[$keyIndex]['name']] =
                                        $this->metaTypeNodeHandler($ite, $mapItem, $keyIndex);
                                } else {
                                    $postMetaArray[$metaColObj[$keyIndex]['name']] =
                                        $this->metaTypeNodeHandler($ite, $mapItem, $keyIndex);
                                }
                            } elseif ($metaColObj[$keyIndex]['wp_term_relationships'] === 1) {
                                $postTermRelation[$metaColObj[$keyIndex]['name']] =
                                    $this->termRelationMetaTypeNodeHandler($ite, $mapItem, $keyIndex);
                            }
                        }
                    }
                } // end of item loop
                /**
                 * Add post type
                 */
                $postArray['post_type'] =
                    $mapItem['destination']['content_type'] !== '' ? $mapItem['destination']['content_type'] : 'post';
                if ($postArray['post_type'] === 'post') {
                    /**
                     * Generate the json structure for Article post multi title
                     */
                    $postMetaArray['multi_title'] =
                        $this->generateMultiTitleArray($postMetaArray['multi_title'], $postArray['post_title']);
                } elseif ($postArray['post_type'] === 'product') {
                    /**
                     * Generate the json structure for product region_info
                     */
                    $postMetaArray['region_info']
                        = ContentTypeUtil::generateRegionInfoArray(
                            $postMetaArray['region_info'],
                            $postArray['post_title']
                        );

                } elseif ($postArray['post_type'] === 'attachment') {
                    $postMetaArray['active'] = 1;
                    $updateTitle = true;
                } elseif ($postArray['post_type'] === 'video') {
                    $postArray['post_type'] = 'attachment';
                    $postArray['post_status'] = 'publish';
                    $postArray['post_mime_type'] = 'video/mp4';
                    $postMetaArray['active'] = 1;
                    if($postMetaArray['jw_player_media_id'] !== ''){
                        $postMetaArray['status'] = 'ready';
                    }
                    $postTermRelation['content_license_int_license_id'] = array(
                        'callback' => 'getAssetImageRightsReferenceByOldIds',
                        'value' => 9,
                        'do_insert' => 'on'
                    );
                }

                /**
                 * Add some extra nodes in Post Meta array
                 */
                $postMetaArray = $this->injectExtraNodeInObject($postArray, $postMetaArray);
                /**
                 * Add some extra nodes in Term Relationship array
                 */
                $postTermRelation = $this->injectExtraNodeInTermRelationShipObject($postTermRelation, $postArray['post_type']);
                /**
                 * code to add details in post table
                 */
                $postID = $this->addPostDetails($postArray, $postMetaArray['old_id_in_onecms'], $updateTitle);
                /**
                 * code to add details in postmeta
                 */
                if ($postID !== 0) {
                    $this->addPostMetaDetails($postMetaArray, $postID);
                }

                $article_bug_id = isset($postArray['article_bug_id']) ?
                    $postArray['article_bug_id'] : 0;
                $article_blog_id = isset($postTermRelation['article_blog_id']['value']) ?
                    $postTermRelation['article_blog_id']['value'] : 0;
                $is_podcast = isset($postTermRelation['is_podcast']['value']) ?
                    $postTermRelation['is_podcast']['value'] : 0;

                $postType = $postArray['post_type'];
                if ($postType === 'attachment' and $postArray['post_mime_type'] === 'video/mp4') {
                    $postType = 'video';
                }
                //Added code to pass the variable in term relation to find insert or update post
                $isPresent =
                    $this->ingesterRepository->postExists($postType, $postMetaArray['old_id_in_onecms']);

                $isUpdate = $isPresent === 0 ? false : true;

                /**
                 * code to add details in term relationships
                 */
                if ($postID !== 0) {
                    $this->addTermRelationshipsDetails(
                        $postTermRelation,
                        $postID,
                        $article_bug_id,
                        $postMetaArray['old_id_in_onecms'],
                        $article_blog_id,
                        $is_podcast,
                        $isUpdate
                    );
                }
                $this->logger->info('----End of migration----------');
                // after every few thousand of records put script execution in sleep mode
                if ((($this->totalInsertCount > 0) && ($this->totalInsertCount % $sleepConst['SLEEP_THRESHOLD'] == 0))
                || (($this->totalUpdateCount > 0) && ($this->totalUpdateCount % $sleepConst['SLEEP_THRESHOLD']) == 0)) {
                    sleep($sleepConst['SLEEP_FOR_SECONDS']);
                }
            } // end of sourceDataObject loop
            $this->logger->info(
                sprintf('Total records inserted {%s}', $this->totalInsertCount)
            );
            $this->logger->info(
                sprintf('Total records updated {%s}', $this->totalUpdateCount)
            );
            $this->logger->info(
                sprintf('Total error records {%s}', $this->totalErrorCount)
            );
        } else {
            $this->logger->info(
                sprintf('Total records lineup for migration {%s}', count($sourceDataObject))
            );
            $this->logger->info('No record to migrate');
        }
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    }

    /**
     * @param $postObject
     * @param $oneCMSId
     * @param $isTitleUpdate
     * @return int
     */
    public function addPostDetails($postObject, $oneCMSId, $isTitleUpdate = false): int
    {
        require_once GlobalConstant::$WP_SETUP;
        require_once GlobalConstant::$WP_TAXONOMY_PATH;
        $oldValueForLog = '';
        $this->logger->info(
            sprintf(
                '----Start migration of {%s} {%s} one cms id {%d}----------',
                $postObject['post_type'],
                $postObject['post_title'],
                $oneCMSId
            )
        );
        if (trim($postObject['post_title']) === '') {
            if ($isTitleUpdate) {
                $title = explode('/', $postObject['guid']);
                $postObject['post_title'] = sanitize_title(end($title));
                $this->logger->info(
                    sprintf(
                        '----{%d}--- Post title is null/blank hence updating by file name {%s}.----',
                        $oneCMSId,
                        $postObject['post_title'],
                    )
                );
            } else {
                $this->logger->error('Post title is blank, can not create migration for this.');
                $this->totalErrorCount++;
                return 0;
            }
        }

        if ($postObject['post_type'] === 'post' || $postObject['post_type'] === 'attachment') {
            if (gettype($postObject['post_author']) === 'array') {
                $postObject['post_author'] = $this->executeCallback($postObject['post_author'], $this->logger);
                $this->logReferenceInfoInLogger('post_author', $postObject['post_author'], '', $oneCMSId);
            } else {
                $postObject['post_author'] = '';
                $this->logReferenceInfoInLogger('post_author', 0);
            }
        }

        if ($postObject['post_type'] === 'post') {
            $postObject['post_content'] =
                gettype($postObject['post_content']) === 'array' ? trim($this->executeCallback($postObject['post_content'])) : '';
        }
        if ($postObject['post_type'] === 'product') {
            $postObject['post_content']
                = $this->setProductPostContent();
        }
        if ($postObject['post_type'] === 'product' || $postObject['post_type'] === 'post') {
            $postObject['post_status'] =
                gettype($postObject['post_status']) === 'array'
                    ? $this->executeCallback($postObject['post_status'], $this->logger, '', '', '', $oneCMSId) : '';
        }

        $postType = $postObject['post_type'];
        if ($postType === 'attachment' and $postObject['post_mime_type'] === 'video/mp4') {
            $postType = 'video';
        }
        $isPresent =
            $this->ingesterRepository->postExists($postType, $oneCMSId);
        /**
         * manually set post date gmt same as post date, to prevent auto change date during update
         */
        $postObject['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime($postObject['post_date']));
        if ($isPresent === 0) {
            // Insert block
            $result = wp_insert_post($postObject, true);
            // For Error handling
            if (is_wp_error($result)) {
                $this->logger->error(
                    sprintf(' --{%d}-- '.$result->get_error_message(), $oneCMSId)
                );
                $this->totalErrorCount++;
                return 0;
            }
            //Insert into post mapping table
            $postID = $result;
            $this->ingesterRepository->insertPostMappingSqlQuery($postID, $oneCMSId, $postType, $this->logger);
            $this->logger->info(
                sprintf(
                    '{%d}-{%d} Action {%s} Post -{%s}- {%s}',
                    $oneCMSId,
                    json_encode($result),
                    GlobalConfig::$LOGGER_KEYS['insert'],
                    $postObject['post_type'],
                    $postObject['post_title'],
                )
            );
            $this->logger->info(json_encode($postObject));
            $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oneCMSId, $result, json_encode($result)));
            $this->totalInsertCount++;
            return $result;
        } else {
            // Update block
            $postObject['ID'] = $isPresent;
            $result = wp_update_post($postObject, true);
            // For Error handling
            if (is_wp_error($result)) {
                $this->logger->error(
                    sprintf(' --{%d}-- '.$result->get_error_message(), $oneCMSId)
                );
                $this->totalErrorCount++;
                return 0;
            }
            $this->logger->info(
                sprintf(
                    '{%d}-{%d} Action {%s} Post -{%s}- {%s}',
                    $oneCMSId,
                    json_encode($result),
                    GlobalConfig::$LOGGER_KEYS['update'],
                    $postObject['post_type'],
                    $postObject['post_title'],
                )
            );
            $this->logger->info(json_encode($postObject));
            $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oneCMSId, $result, json_encode($result)));
            $this->totalUpdateCount++;
            return $result;
        }
    }

    /**
     * @param $postMeta
     * @param $postId
     */
    public function addPostMetaDetails($postMeta, $postId)
    {
        if (count($postMeta) > 0) {
            $oldCMSId = $postMeta['old_id_in_onecms'];
            foreach ($postMeta as $key => $itm) {
                // check if any callback is present
                if (gettype($itm) === 'array' && isset($itm['callback']) && $itm['callback'] !== '') {
                    $itm = $this->executeCallback($itm, $this->logger, $postId, $key, '', $oldCMSId);
                    $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oldCMSId, $postId, $itm));
                    continue;
                }
                if (!$this->ingesterRepository->isMetaDataExists('post', $postId, $key)) {
                    if (strpos($itm, 'ref_error') !== false  && !is_array($itm)) {
                        $actualItem = $itm;
                        $itm = '';
                        $result = update_post_meta($postId, $key, $itm, false);
                        $this->logReferenceInfoInLogger($key, $actualItem, $postId, $oldCMSId);
                    } else {
                        $result = update_post_meta($postId, $key, $itm, false);
                        $this->logReferenceInfoInLogger($key, $itm, $postId, $oldCMSId);
                    }
                    $this->logger->info(
                        sprintf(
                            '{%d}-{%d} Action {%s} postmeta -post id- {%d} -meta key- {%s} -keyvalue- {%s}',
                            $oldCMSId,
                            $postId,
                            GlobalConfig::$LOGGER_KEYS['insert'],
                            $postId,
                            $key,
                            $itm
                        )
                    );
                    $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oldCMSId, $postId, $result));
                } else {
                    if (strpos($itm, 'ref_error') !== false  && !is_array($itm)) {
                        $actualItem = $itm;
                        $itm = '';
                        $result = update_post_meta($postId, $key, $itm, '');
                        $this->logReferenceInfoInLogger($key, $actualItem, $postId, $oldCMSId);
                    } else {
                        $result = update_post_meta($postId, $key, $itm, '');
                        $this->logReferenceInfoInLogger($key, $itm, $postId, $oldCMSId);
                    }
                    $this->logger->info(
                        sprintf(
                            '{%d}-{%d} Action {%s} postmeta -post id- {%d} -meta key- {%s} -keyvalue- {%s}',
                            $oldCMSId,
                            $postId,
                            GlobalConfig::$LOGGER_KEYS['update'],
                            $postId,
                            $key,
                            $itm
                        )
                    );
                    $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oldCMSId, $postId, $result));
                }
            }
        }
    }

    /**
     * @param $termRelationArray
     * @param $postId
     * @param int $article_bug_id
     * @param int $oldCMSID
     * @param int $blogId
     * @param bool $isPodcast
     * @return string
     */
    public function addTermRelationshipsDetails(
        $termRelationArray,
        $postId,
        $article_bug_id = 0,
        $oldCMSID = 0,
        $blogId = 0,
        $isPodcast = false,
        $isUpdate = false
    ): string {
        if (count($termRelationArray) > 0) {
            foreach ($termRelationArray as $key => $item) {
                if (gettype($item) === 'array' && isset($item['callback']) && $item['callback'] !== '') {
                    $this->executeCallback(
                        $item,
                        $this->logger,
                        $postId,
                        $key,
                        $article_bug_id,
                        $oldCMSID,
                        $blogId,
                        $isPodcast,
                        $isUpdate
                    );
                }
            }
        }
        return '';
    }

    /**
     * @param $ite
     * @param $mapItem
     * @param $keyIndex
     * @return mixed
     */
    public function nonMetaTypeNodeHandler($ite, $mapItem, $keyIndex)
    {
        // assign map destination cols, just to save length of line during multiple use
        $metaColObj = $mapItem['destination']['cols'];
        if ($metaColObj[$keyIndex]['is_callback'] !== '') {
            return [
                'callback' => $metaColObj[$keyIndex]['is_callback'],
                'value' => $ite,
                'do_insert' => $metaColObj[$keyIndex]['do_insert']
            ];
        } else {
            return $ite;
        }
    }

    /**
     * @param $ite
     * @param $mapItem
     * @param $keyIndex
     * @return mixed
     */
    public function metaTypeNodeHandler($ite, $mapItem, $keyIndex)
    {
        // assign map destination cols, just to save length of line during multiple use
        $metaColObj = $mapItem['destination']['cols'];
        if ($metaColObj[$keyIndex]['is_callback'] !== '') {
            return [
                'callback' => $metaColObj[$keyIndex]['is_callback'],
                'value' => $ite,
                'do_insert' => $metaColObj[$keyIndex]['do_insert']
            ];
        } elseif ($metaColObj[$keyIndex]['multi_title'] === 1) {
            return [
                'path' => $metaColObj[$keyIndex]['path_key'],
                'value' => $ite
            ];
        } elseif ($metaColObj[$keyIndex]['region_info'] === 1) {
            return [
                'path' => $metaColObj[$keyIndex]['path_key'],
                'value' => $ite
            ];
        } else {
            return $ite;
        }
    }

    /**
     * @param $ite
     * @param $mapItem
     * @param $keyIndex
     * @return mixed
     */
    public function termRelationMetaTypeNodeHandler($ite, $mapItem, $keyIndex)
    {
        // assign map destination cols, just to save length of line during multiple use
        $metaColObj = $mapItem['destination']['cols'];
        if ($metaColObj[$keyIndex]['is_callback'] !== '') {
            return [
                'callback' => $metaColObj[$keyIndex]['is_callback'],
                'value' => $ite,
                'do_insert' => $metaColObj[$keyIndex]['do_insert']
            ];
        } else {
            return $ite;
        }
    }

    /**
     * @param $callbackArray
     * @param Logger|null $loggerObject
     * @param int $postId
     * @param string $key
     * @param int $article_bug_id
     * @param int $oldCMSId
     * @param int $blogId
     * @param bool $isPodcast
     * @param bool $isUpdate
     * @return mixed
     * @uses
     * sanitizeContent:                         Case to sanitize xhtml content
     * getStatusReferenceByOldSlug:             Case to update article status based on CMS work flow
     * getAuthorReferenceByOldIdsForMeta:       Case to replace all the user id or ids with their updated reference
     *                                          and convert it in json
     * getImageReferenceByOldId:                Case to replace all the Image id with their updated reference
     * getCategoryReferenceByOldIds:            Case to replace all the category id/ids with their updated reference
     * getTagReferenceByOldIds:                 Case to replace all the Tag id/ids with their updated reference
     * getArticleTypeReferenceByOldIds:         Case to replace Article Type(known as: story type) id
     *                                          with their updated reference
     * getArticleDisplayReferenceByOldIds:      Special case where reference is decided on article display id
     *                                          and article bug id
     * getArticleSponsorshipReferenceByOldIds:  Case to replace Sponsorship id with their updated reference
     * getArticleBlogReferenceByOldIds:         Case to replace all the Blog id/ids with their updated reference
     *                                          This is a special case, please see the actual block for detail
     * getArticlePodcastReferenceByOldIds:      Case to replace all the Podcast id/ids with their updated reference
     *                                          This is a special case, please see the actual block for detail
     * getAuthorReferenceByOldIds:              Case to replace only first reference of id/s
     * getBusinessUnitCallback:                 Case to add business unit and publication
     * getMaufactureDisplayReferenceByOldIds:   Case to replace all the company/manufacture/ids with their updated reference
     * getTerritoriesReferenceByOldIds:         Case to add reference for Territories
     * getCmsOriginReferenceByOldIds:           Case to add reference for CmsOrigin
     * getVendorCodeReferenceByOldIds:          Case to add reference for VendorCode
     * getAssetTagReferenceByOldIds:            Case to add reference for AssetsTag
     * getAssetImageRightsReferenceByOldIds:    Case to add reference for AssetsImageRights
     * getGlobalInfoReferenceByOldIds           Case to add reference for GlobalInfo
     * getVideoReferenceByOldId                 Case to add reference for feature videos
     */
    public function executeCallback(
        $callbackArray,
        Logger $loggerObject = null,
        $postId = 0,
        $key = '',
        $article_bug_id = 0,
        $oldCMSId = 0,
        $blogId = 0,
        $isPodcast = false,
        $isUpdate = false
    ) {
        switch ($callbackArray['callback']) {
            case 'sanitizeContent':
                return $this->dataFilter->sanitizePostContent($callbackArray, true);
            case 'getStatusReferenceByOldSlug':
                $newStatusArr = ContentTypeUtil::articleWorkflowStatusLinkMapping(
                    $callbackArray['value'],
                    $callbackArray['do_insert'],
                    $oldCMSId,
                    0,
                    $loggerObject
                );
                if (count($newStatusArr) > 0) {
                    return $newStatusArr['new_status'];
                } else {
                    return '';
                }
            case 'getAuthorReferenceByOldIds':
            case 'getAuthorReferenceByOldIdsForMeta':
                if ($callbackArray['callback'] === 'getAuthorReferenceByOldIdsForMeta') {
                    $authorArray = ContentTypeUtil::authorMapping(
                        $callbackArray['value'],
                        $callbackArray['do_insert'],
                        $postId,
                        $loggerObject,
                        false,
                        $oldCMSId,
                        $key
                    );
                } elseif ($callbackArray['callback'] === 'getAuthorReferenceByOldIds') {
                    $author = ContentTypeUtil::authorMapping(
                        $callbackArray['value'],
                        $callbackArray['do_insert'],
                        $postId,
                        $loggerObject,
                        true,
                        $oldCMSId
                    );
                    if ($author > 0) {
                        return $author;
                    } else {
                        return 0;
                    }
                }
                return '';
            case 'getImageReferenceByOldId':
                return ContentTypeUtil::imageMapping(
                    $callbackArray['value'],
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $oldCMSId
                );
            case 'getProductImageReferenceByOldId':
                return ContentTypeUtil::imageMapping(
                    $callbackArray['value'],
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $oldCMSId,
                    1
                );
            case 'getCategoryReferenceByOldIds':
                return ContentTypeUtil::articleCategoryLinkMapping(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $isUpdate
                );
            case 'getTagReferenceByOldIds':
                return ContentTypeUtil::articleTagsLinkMapping(
                    $callbackArray['value'],
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $oldCMSId
                );
            case 'getArticleTypeReferenceByOldIds':
                return ContentTypeUtil::articleStoryTypeLinkMapping(
                    $callbackArray['value'],
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $oldCMSId
                );
            case 'getArticleDisplayReferenceByOldIds':
                return ContentTypeUtil::articleArticleTypeLinkMapping(
                    $article_bug_id,
                    $callbackArray['value'],
                    $callbackArray['do_insert'],
                    $oldCMSId,
                    $postId,
                    $loggerObject
                );
            case 'getArticleSponsorshipReferenceByOldIds':
                $sponsorRefArr = ContentTypeUtil::articleSponsorshipLinkMapping(
                    $callbackArray['value'],
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $oldCMSId
                );
                if (count($sponsorRefArr) > 0) {
                    return $sponsorRefArr[0];
                } else {
                    return '';
                }
            case 'getArticleBlogReferenceByOldIds':
                /**
                 * if is_podcast is NOT true then only will consider it for blog reference
                 */
                if ($isPodcast !== true) {
                    return ContentTypeUtil::articleBlogMapping(
                        $callbackArray['value'],
                        $callbackArray['do_insert'],
                        $postId,
                        $loggerObject,
                        $oldCMSId
                    );
                } else {
                    return '';
                }
            case 'getArticlePodcastReferenceByOldIds':
                /**
                 * if is_podcast is true then replace it's value with blogId,
                 * because we actually need to find reference of blog
                */
                if ($callbackArray['value'] === true) {
                    $callbackArray['value'] = $blogId;
                    return ContentTypeUtil::articlePodcastMapping(
                        $callbackArray['value'],
                        $callbackArray['do_insert'],
                        $postId,
                        $loggerObject,
                        $oldCMSId
                    );
                } else {
                    return '';
                }
            case 'getBusinessUnitCallback':
                ContentTypeUtil::addArticleBusinessUnitLink(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject
                );
                break;
            case 'getPublicationCallback':
                ContentTypeUtil::addArticlePublicationLink(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject
                );
                break;
            case 'getMaufactureDisplayReferenceByOldIds':
                ContentTypeUtil::productMaufactureLinkMapping(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $isUpdate
                );
                break;
            case 'getTerritoriesReferenceByOldIds':
                ContentTypeUtil::productTerritoriesLinkMapping(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $isUpdate
                );
                break;
            case 'getCmsOriginReferenceByOldIds':
                ContentTypeUtil::productCmsOriginLinkMapping(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $isUpdate
                );
                break;
            case 'getVendorCodeReferenceByOldIds':
                ContentTypeUtil::productVendorCodeLinkMapping(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $isUpdate
                );
                break;
            case 'getProductsArticlesReferenceByOldIds':
                ContentTypeUtil::productArticlesLinkMapping(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    $isUpdate
                );
                break;
            case 'getAssetTagReferenceByOldIds':
                return ContentTypeUtil::attachmentAssetsTagLinkMapping(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject
                );
            case 'getAssetImageRightsReferenceByOldIds':
                return ContentTypeUtil::attachmentAssetsImageRightsLinkMapping(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject
                );
            case 'getGlobalInfoReferenceByOldIds':
                ContentTypeUtil::globalInfoLinkMapping(
                    $callbackArray['value'],
                    $oldCMSId,
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject
                );
                break;
            case 'getVideoReferenceByOldId':
                // A function call to handle video reference resolve
                ContentTypeUtil::videoMapping(
                    $callbackArray['value'],
                    $callbackArray['do_insert'],
                    $postId,
                    $loggerObject,
                    true,
                    $oldCMSId
                );
                break;
            break;
        }
    }

    /**
     * @param $key
     * @param $newRefId
     * @param int $postId
     * @param int $oldCMSId
     */
    public function logReferenceInfoInLogger($key, $newRefId, $postId = 0, $oldCMSId = 0)
    {
        if (in_array($key, $this->referenceColumns)) {
            if (gettype($newRefId) !== 'array' && strpos($newRefId, 'ref_error') !== false) {
                $errArray = explode('|', $newRefId);
                $this->logger->error(
                    sprintf(
                        '{%d}-{%d} Reference NOT found {%s} with id {%d}',
                        $oldCMSId,
                        $postId,
                        $key,
                        $errArray[1]
                    )
                );
            } else {
                $this->logger->info(
                    sprintf(
                        '{%d}-{%d} Reference of {%s} change to {%d} now ',
                        $oldCMSId,
                        $postId,
                        $key,
                        $newRefId
                    )
                );
            }
        }
    }

    /**
     * @param $multiTitleArray
     * @param $postTitle
     */
    public function generateMultiTitleArray($multiTitleArray, $postTitle = '')
    {
        $arrayStructure = array (
            'titles' =>
                array (
                    'headline' =>
                        array (
                            'value' => '',
                            'additional' =>
                                array (
                                    'headline_subheadline' => '',
                                    'headline_desc' => '',
                                ),
                        ),
                    'seo' =>
                        array (
                            'value' => '',
                            'additional' =>
                                array (
                                    'seo_slug' => '',
                                    'seo_desc' => '',
                                    'seo_canonical_url' => '',
                                ),
                        ),
                    'social' =>
                        array (
                            'value' => '',
                            'additional' =>
                                array (
                                    'social_desc' => '',
                                ),
                        ),
                ),
            'subtitles' =>
                array (
                ),
        );
        if (count($multiTitleArray) > 0) {
            $arrayStructure['titles']['headline']['value'] = $postTitle;
            $arrayStructure['titles']['headline']['additional']['headline_subheadline'] =
                $multiTitleArray['deck']['value'];
            $arrayStructure['titles']['headline']['additional']['headline_desc'] = $multiTitleArray['summary']['value'];
            $arrayStructure['titles']['seo']['value'] = $multiTitleArray['meta_title']['value'];
            $arrayStructure['titles']['seo']['additional']['seo_slug'] = $multiTitleArray['slug']['value'];
            $arrayStructure['titles']['seo']['additional']['seo_desc'] = $multiTitleArray['meta_description']['value'];
            $arrayStructure['titles']['seo']['additional']['seo_canonical_url'] = $multiTitleArray['canonical_url']['value'];
            $arrayStructure['titles']['social']['value'] = $multiTitleArray['og_title']['value'];
            $arrayStructure['titles']['social']['additional']['social_desc'] =
                $multiTitleArray['og_description']['value'];
        }
        return wp_slash(json_encode($arrayStructure));
    }

    public function injectExtraNodeInObject($postArray, $postMetaArray):array
    {
        switch ($postArray['post_type']) {
            case 'post':
                $postMetaArray = $this->ingesterRepository->injectExtraValueInMetaObject(
                    $postMetaArray,
                    '',
                    '',
                    '_idg_copyright_info'
                );
                $postMetaArray = $this->ingesterRepository->injectExtraValueInMetaObject(
                    $postMetaArray,
                    $postMetaArray['old_id_in_onecms'].'/'.$postArray['post_name'],
                    '',
                    'old_slug_in_onecms'
                );
                /*$postMetaArray = $this->ingesterRepository->injectExtraValueInMetaObject(
                    $postMetaArray,
                    $this->ingesterRepository->getBusinessUnit(),
                    '',
                    'business_unit'
                );
                $postMetaArray = $this->ingesterRepository->injectExtraValueInMetaObject(
                    $postMetaArray,
                    (array)$this->ingesterRepository->getPublication(),
                    '',
                    'publication'
                );*/

                /**
                 * Replace the post_author(source:updated_by) values with $postArray['post_author']['value']
                 * in post meta will need to store all the user id/ids present in post_author before replacing
                 * it with it's reference
                 */
                $postMetaArray['post_author']['value'] = $postArray['post_author']['value'];
                $postMetaArray = $this->ingesterRepository->injectExtraValueInMetaObject(
                    $postMetaArray,
                    '',
                    '',
                    'reviews'
                );
                $postMetaArray = $this->ingesterRepository->injectExtraValueInMetaObject(
                    $postMetaArray,
                    0,
                    '',
                    'is_transformed'
                );
                break;
        }
        return $postMetaArray;
    }

    public function injectExtraNodeInTermRelationShipObject($relationObject, $contentType):array
    {
        switch ($contentType) {
            case 'post':
                $businessArray = [
                "callback" => 'getBusinessUnitCallback',
                "value" =>  [
                                $this->ingesterRepository->getBusinessUnit(),
                                $this->ingesterRepository->getPublication()
                            ],
                "do_insert" => "on"
                ];
                $relationObject['publication'] = $businessArray;
                break;
            case 'product':
                $relationObject['territories'] = [
                "callback" => 'getTerritoriesReferenceByOldIds',
                "value" =>  '',
                "do_insert" => "on"

                ];
                $relationObject['cmsorigin'] = [
                "callback" => 'getCmsOriginReferenceByOldIds',
                "value" =>  '',
                "do_insert" => "on"
                ];
                break;
        }
        return $relationObject;
    }

    public function setProductPostContent()
    {
        return '<!-- wp:cf/block /-->';
    }
}

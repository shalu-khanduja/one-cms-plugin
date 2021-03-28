<?php


namespace IDG2Migration\modules;

use IDG2Migration\config\GlobalConfig;
use IDG2Migration\config\GlobalConstant;
use IDG2Migration\db\SourceConnection;
use IDG2Migration\helpers\DataFilter;
use PDO;
use PDOException;

class FilterContentModule
{
    /**
     * @var DataFilter
     */
    private DataFilter $dataFilter;

    public function __construct($logFileName = '')
    {
        $this->dataFilter = new DataFilter();
    }
    /**
     * @return mixed
     */
    private function connectSourceDB()
    {
        try {
            return SourceConnection::get()->connect();
        } catch (PDOException $e) {
            return $e;
        }
    }

    public function getAllPostContent($queryLimit, $queryOffset)
    {
        require_once GlobalConstant::$WP_SETUP;
        global $wpdb;
        $extraString = '';
        $extraString .= $queryLimit !== '' ? ' limit '.$queryLimit : '';
        $extraString .= $queryOffset !== '' ? ' offset '.$queryOffset : '';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id,post_content FROM ".$wpdb->prefix."posts WHERE `post_type` = %s order by id asc".$extraString,
                array('post')
            )
        );
    }

    /**
     * @param $queryLimit
     * @param $queryOffset
     * @param string $articleList
     * @return mixed
     */
    public function getAllUntransformedPostContent($queryLimit, $queryOffset, $articleList = "")
    {
        require_once GlobalConstant::$WP_SETUP;
        global $wpdb;
        $extraString = '';
        $extraString .= $queryLimit !== '' ? ' limit '.$queryLimit : '';
        $extraString .= $queryOffset !== '' ? ' offset '.$queryOffset : '';
        $extraInClause = ($articleList !== '') ? 'AND post.ID IN ('.$articleList.')' : '';
        // TODO: revisit meta_value IN (0,1) condition over OR in case there is performance issue in 83K
        $sql = 'SELECT id, post_content, post_date
                    FROM '.$wpdb->prefix.'posts as post
                    INNER JOIN '.$wpdb->prefix.'postmeta as meta
                    ON post.ID = meta.post_id
                    WHERE
                    post.post_type = "%1s"
                    %1s
                    AND meta.meta_key = "is_transformed"
                    AND meta.meta_value IN (0,1) order by post.ID ASC %1s';
        return $wpdb->get_results(
            $wpdb->prepare(
                $sql,
                array('post',$extraInClause,$extraString)
            )
        );
    }

    /**
     * @param $id <p>post id</p>
     * @param $updatedContent <p>Updated content which need's to update</p>
     * @return mixed
     */
    public function saveUpdatedContent($id, $updatedContent)
    {
        require_once GlobalConstant::$WP_SETUP;
        require_once GlobalConstant::$WP_POST_PATH;
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        $postArr = array(
            'ID'           => $id,
            'post_content' => $updatedContent,
        );
        // Update the post into the database
        $result = wp_update_post($postArr);
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        return $result;
    }

    /**
     * @param mixed $queryLimit
     * @param mixed $queryOffset
     *
     * @return array
     */
    public function getInactivePostTags($queryLimit, $queryOffset) {
        $extraString = '';
        $extraString .= $queryLimit !== '' ? ' limit '.$queryLimit : '';
        $extraString .= $queryOffset !== '' ? ' offset '.$queryOffset : '';

        $sql = 'SELECT id FROM tag
                WHERE status = 0 OR status is null
                ORDER BY id ' . $extraString;
        $sourceDB = $this->connectSourceDB();
        return $sourceDB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param mixed $queryLimit
     * @param mixed $queryOffset
     *
     * @return array
     */
    public function getInactiveImageTags($queryLimit, $queryOffset) {
        $extraString = '';
        $extraString .= $queryLimit !== '' ? ' limit '.$queryLimit : '';
        $extraString .= $queryOffset !== '' ? ' offset '.$queryOffset : '';

        $sql = 'SELECT
                tag.id
                FROM
                    tag
                    INNER JOIN tag_content_int ON tag.id = tag_content_int.tag_id
                    INNER JOIN content_license_int ON tag_content_int.content_id = content_license_int.content_id
                WHERE
                    tag_content_int.content_type_id = 6
                    AND content_license_int.license_id not in (13, 11, 61, 62, 59, 2, 8, 5)
                    AND (tag.status = 0 OR tag.status is null)
                group by
                    tag.label,
                    tag.tag,
                    tag.id
                order by tag.id ' . $extraString;
        $sourceDB = $this->connectSourceDB();
        return $sourceDB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $ref_taxonomy
     * @param $value
     * @param $meta_key
     *
     * @return mixed
     */
    public function getNewReferenceIdByOld($ref_taxonomy, $value, $meta_key = 'old_id_in_onecms')
    {
        require_once GlobalConstant::$WP_SETUP;
        global $wpdb;
        if (!empty($value)) {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    'select tm.term_id from '.$wpdb->prefix.'termmeta as tm, '.$wpdb->prefix.'term_taxonomy as tt
                    where
                    tm.meta_key = %s
                    AND tm.meta_value = %d
                    AND tm.term_id = tt.term_id
                    AND tt.taxonomy = %s',
                    $meta_key,
                    $value,
                    $ref_taxonomy
                )
            );

            if (count($results) > 0) {
                return $results[0]->term_id;
            } else {
                return 'ref_error';
            }
        } else {
            return 0;
        }
    }
//    /**
//     * @return array
//     */
//    public function getSingleAndMultiReviewsFromSource(): array
//    {
//        $sourceDB = $this->connectSourceDB();
//        $newArray = [];
//        if (get_class($sourceDB) !== 'PDOException') {
//            $sourceSql = "select pa.article_id, product_id, pa.sort_order, is_capsule, is_at_a_glance,
//                            insert_type, article_type_id, strategy_ids
//                        from products_articles pa
//                        join article a on pa.article_id=a.id
//                        join product p on pa.product_id=p.id
//                        left join (
//                            select content_id as article_id, array_agg(content_strategy_id) as strategy_ids
//                            from content_strategy_int
//                            where content_type_id=1
//                            group by content_id
//                        ) csi on pa.article_id=csi.article_id
//                        where a.statusid=6 /*live articles*/ and a.brand_owner=1 /*macworld articles*/
//                        and a.article_type_id=3
//                        and p.status=6 /*live products*/ and is_at_a_glance=true
//                        and (3 != ANY(strategy_ids) or 4 != ANY(strategy_ids))
//                        order by article_id desc, pa.sort_order asc";
//            $sth = $sourceDB->prepare($sourceSql);
//            $sth->execute();
//
//            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
//            $newArray = $this->makeArrayGroup($result, $sourceDB);
//            return $newArray;
//        } else {
//            echo "Source Database is not connected.".PHP_EOL;
//        }
//        return $newArray;
//    }

    /**
     * @return array
     */
    public function getSingleAndMultiReviewsFromSource(): array
    {
        $sourceDB = $this->connectSourceDB();
        $newArray = [];
        if (get_class($sourceDB) !== 'PDOException') {
            $sourceSql = "select
                              pa.article_id,
                              pa.product_id,
                              pa.sort_order,
                              is_capsule,
                              is_at_a_glance,
                              insert_type,
                              article_type_id,
                              strategy_ids,
                              array_to_string(Array_agg(phpro.highlight ORDER BY phpro.display_order ASC), '|P_P_C|') AS product_pros,
                              array_to_string(Array_agg(phcon.highlight ORDER BY phcon.display_order ASC), '|P_P_C|') AS product_cons,
                              pef.blurb, pef.rating, pef.is_awarded
                            from
                              products_articles pa
                              join article a on pa.article_id = a.id
                              join product p on pa.product_id = p.id
                              left join (
                                select
                                  content_id as article_id,
                                  array_agg(content_strategy_id) as strategy_ids
                                from
                                  content_strategy_int
                                where
                                  content_type_id = 1
                                group by
                                  content_id
                              ) csi on pa.article_id = csi.article_id
                              left join
                              product_highlight phpro on phpro.product_id = p.id
                              AND (phpro.brand_display_perms & 1) > 0 AND phpro.product_highlight_type_id = 1
                              left join
                              product_highlight phcon on phcon.product_id = p.id
                              AND (phcon.brand_display_perms & 1) > 0 AND phcon.product_highlight_type_id = 2
                              left join
                              product_edit_info pef on pef.product_id = p.id and pef.brand_owner=1
                            where
                              a.statusid = 6
                              /*live articles*/
                              and a.brand_owner = 1
                              /*macworld articles*/
                              and a.article_type_id = 3
                              and p.status = 6
                              /*live products*/
                              and is_at_a_glance = true
                              and (
                                strategy_ids is null OR
                                (3 != ALL(strategy_ids)
                                AND 4 != ALL(strategy_ids))
                              )
                              group by pa.article_id, pa.product_id, pa.sort_order, pa.is_capsule, pa.is_at_a_glance,
                              pa.insert_type, a.article_type_id, strategy_ids,pef.blurb, pef.rating, pef.is_awarded
                            order by
                              article_id desc,
                              pa.sort_order asc";
            $sth = $sourceDB->prepare($sourceSql);
            $sth->execute();

            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            $newArray = $this->makeArrayGroup($result, $sourceDB);
            return $newArray;
        } else {
            echo "Source Database is not connected.".PHP_EOL;
        }
        return $newArray;
    }

    /**
     * @param $result
     * @param null $sourceDB
     * @return array
     */
    public function makeArrayGroup($result, $sourceDB = null): array
    {
        $newArray = [];
        foreach ($result as $item) {
            // fetch new post_id by old article ID
            $item['newProductId'] = $this->getNewPostIdByOldOneCMSId($item['product_id'], 'product');
            //$item['product_highlight_info'] = $this->getProductHighlightInfo($item['product_id'], $sourceDB);
            //$item['product_edit_info'] = $this->getProductEditInfo($item['product_id'], $sourceDB);
            $newId = $this->getNewPostIdByOldOneCMSId($item['article_id']);
            $item['newArticleId'] = $newId;
            $newArray[$newId][] = $item;
        }
        return $newArray;
    }

    /**
     * @param $oldId
     * @param string $postType
     * @return mixed
     */
    public function getNewPostIdByOldOneCMSId($oldId, $postType = 'post')
    {
        require_once GlobalConstant::$WP_SETUP;
        if ($oldId > 0) {
            global $wpdb;
            /*$sql = 'SELECT
                    post_id
                    FROM
                    '.$wpdb->prefix.'postmeta
                    INNER JOIN '.$wpdb->prefix.'posts
                    ON '.$wpdb->prefix.'postmeta.post_id = '.$wpdb->prefix.'posts.ID
                    AND
                    '.$wpdb->prefix.'posts.post_type = %s
                    WHERE
                    meta_key = "old_id_in_onecms"
                    AND
                    meta_value = %s';*/
            $sql = 'SELECT post_id FROM migration_post_mapping WHERE type = %s AND old_id_in_onecms = %d';
            $result = $wpdb->get_row($wpdb->prepare($sql, array($postType, $oldId)), ARRAY_A);
            return $result['post_id'];
        }
        return;
    }

    /**
     * @return array
     */
    public function getReviewChartFromSource(): array
    {
        $sourceDB = $this->connectSourceDB();
        if (get_class($sourceDB) !== 'PDOException') {
            $sourceSql = "select pa.article_id, pa.product_id, pa.sort_order, is_capsule, is_at_a_glance, insert_type,
                        article_type_id, strategy_ids, pei.blurb, pei.rating, pei.chart_summary
                        from products_articles pa
                        join article a on pa.article_id=a.id
                        join product p on pa.product_id=p.id
                        left join (
                            select content_id as article_id, array_agg(content_strategy_id) as strategy_ids
                            from content_strategy_int
                            where content_type_id=1
                            group by content_id
                        ) csi on pa.article_id=csi.article_id
                        left join
                            product_edit_info pei on pa.product_id = pei.product_id and pei.brand_owner = 1
                        where a.statusid=6 /*live articles*/ and a.brand_owner=1 /*macworld articles*/
                        and p.status=6 /*live products*/
                        and is_at_a_glance=true
                        and (a.article_type_id != 3 OR
                            (a.article_type_id=3 AND (3= ANY(strategy_ids) or 4= ANY(strategy_ids))))
                        order by article_id desc, pa.sort_order asc";
            $sth = $sourceDB->prepare($sourceSql);
            $sth->execute();

            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            return $this->filterItemForReviewChart($result);
        } else {
            echo "Source Database is not connected.".PHP_EOL;
            exit;
        }
        return [];
    }

    /**
     * @param $result
     * @return array
     */
    public function filterItemForReviewChart($result): array
    {
        $newArray = [];
        foreach ($result as $item) {
            $newId = $this->getNewPostIdByOldOneCMSId($item['article_id']);
            $newProdId = $this->getNewPostIdByOldOneCMSId($item['product_id'], 'product');
            $item['newProductId'] = $newProdId;
            $newArray[$newId][] = $item;
        }

        return $newArray;
    }

    /**
     * @param $articleId
     * @param $productArray
     * @param $loggerObj
     * @return string
     */
    public function generateProductChartBlock($articleId, $productArray, $loggerObj = null): string
    {
        $chartItems = '';
        $counter = 1;
        $productChartBlock = [];
        foreach ($productArray as $key => $item) {
            if ((int)$item['newProductId'] > 0) {
                $productContent = !empty($item['chart_summary']) ? $item['chart_summary'] : '';
                $rating = !empty($item['rating']) ? (float)$item['rating'] : 0;
                $chartBlockArr = [
                    'rank' => $counter,
                    'productId' => (int)$item['newProductId'],
                    'productTitle' => '',
                    'titleOverride' => false,
                    'productContent' => $productContent,
                    'productRating' => $rating,
                    'ratingOverride' => $rating != 0,
                    'productImageSize' => 'Small',
                    'productImage' => 0,
                    'imageFromOrigin' => true
                ];

                $productChartBlock['productData'][] = $chartBlockArr;
                $productDetails = [
                    'rank' => $counter,
                    'productId' => (int)$item['newProductId'],
                    'productContent' => $productContent,
                ];
                if ($rating != 0) {
                    $productDetails['productRating'] = $rating;
                    $productDetails['ratingOverride'] = true;
                }
                $chartItems.= '<!-- wp:idg-base-theme/product-chart-item '
                    .trim(wp_slash(
                        json_encode(
                            $productDetails,
                            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
                        )
                    ), '[]').' /-->';
                $counter++;
            } else {
                $loggerObj->info(
                    sprintf(
                        '--destination product not found for article {%d} and product id {%d} ',
                        $articleId,
                        $item['product_id']
                    )
                );
            }
        }// end of foreach
        $productChartBlock['isShowingRank'] = false;
        $productChartBlock['linksInNewTab'] = true;
        $productBlockStructure = (!empty($productChartBlock)) ?
            wp_slash(
                json_encode(
                    $productChartBlock,
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
                )
            ) : '';

        return '<!-- wp:idg-base-theme/product-chart-block '.$productBlockStructure.' -->'.
            '<div class="wp-block-idg-base-theme-product-chart-block wp-block-product-chart product-chart">'.
            $chartItems.'</div><!-- /wp:idg-base-theme/product-chart-block -->';
    }

    /**
     * @param $articleId
     * @param $productArray
     * @param null $loggerObj
     * @return string
     */
    public function generateProductReviewBlock($articleId, $productArray, $loggerObj = null): string
    {
        $reviewBlock = '';
        // if product ID not available create a blank review structure
        if ((int)$productArray[0]['newProductId'] <= 0) {
            $reviewBlock .= '<!-- wp:idg-base-theme/review-block -->';
            $htmlBlock = '<div class="review-columns"><div class="review-column"><h3 class="review-subTitle">Pros</h3><ul class="pros review-list"><li></li></ul></div><div class="review-column"><h3 class="review-subTitle">Cons</h3><ul class="cons review-list"><li></li></ul></div></div><h3 class="review-subTitle review-subTitle--borderTop">Our Verdict</h3><p class="verdict"></p>';
            $reviewBlock .= $htmlBlock.'<!-- /wp:idg-base-theme/review-block -->';
            return $reviewBlock;
        }

        $initialArray = [
            "heading" => "At a Glance",
            "primaryProductId" => (int)$productArray[0]['newProductId'],
            "rating" => $productArray[0]['rating'] != ''
                ? (float)$productArray[0]['rating'] : 0,
            "editorsChoice" => $productArray[0]['is_awarded'] === true
        ];
        $reviewBlock .= '<!-- wp:idg-base-theme/review-block '.wp_slash(json_encode($initialArray, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)).' -->';
        $prosHtml = '';
        $consHtml = '';
        if ($productArray[0]['product_pros'] !== '') {
            $prosArr = explode('|P_P_C|', $productArray[0]['product_pros']);
            $uniqProsArr = array_unique($prosArr);
            foreach ($uniqProsArr as $key => $item) {
                $prosHtml .= '<li>'.trim($item).'</li>';
            }
        }
        if ($productArray[0]['product_cons'] !== '') {
            $consArr = explode('|P_P_C|', $productArray[0]['product_cons']);
            $uniqConsArr = array_unique($consArr);
            foreach ($uniqConsArr as $key => $item) {
                $consHtml .= '<li>'.trim($item).'</li>';
            }
        }
        $htmlBlock = '<div class="review-columns"><div class="review-column">';
        $htmlBlock .= '<h3 class="review-subTitle">Pros</h3>';
        if ($prosHtml !== '') {
            $htmlBlock .= '<ul class="pros review-list">'.$prosHtml.'</ul>';
        } else {
            $htmlBlock .= '<ul class="pros review-list"><li></li></ul>';
        }
        $htmlBlock .= '</div><div class="review-column">';
        $htmlBlock .= '<h3 class="review-subTitle">Cons</h3>';
        if ($consHtml !== '') {
            $htmlBlock .= '<ul class="cons review-list">'.$consHtml.'</ul>';
        } else {
            $htmlBlock .= '<ul class="cons review-list"><li></li></ul>';
        }
        $htmlBlock .= '</div></div>';
        $htmlBlock .= '<h3 class="review-subTitle review-subTitle--borderTop">Our Verdict</h3>';
        if ($productArray[0]['blurb'] !== '') {
            $htmlBlock .= '<p class="verdict">'.$productArray[0]['blurb'].'</p>';
        } else {
            $htmlBlock .= '<p class="verdict"></p>';
        }
        $reviewBlock .= $htmlBlock.'<!-- /wp:idg-base-theme/review-block -->';
        return $reviewBlock;
    }

    /**
     * @param $productId
     * @param $sourceDB
     * @return mixed
     */
    public function getProductHighlightInfo($productId, $sourceDB)
    {
        $sql = "SELECT ph.highlight, ph.display_order, ph.product_highlight_type_id
	            FROM
	            product_highlight ph
                WHERE ph.product_id = ?
                AND (ph.brand_display_perms & 1) > 0 order by ph.display_order asc";
        $sth = $sourceDB->prepare($sql);
        $sth->execute(array($productId));

        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * @param $productId
     * @param $sourceDB
     * @return mixed
     */
    public function getProductEditInfo($productId, $sourceDB)
    {
        $sql = "SELECT pef.blurb, pef.rating, pef.chart_summary, pef.is_awarded
	            FROM
	            product_edit_info pef
                WHERE pef.product_id = ?
                AND pef.brand_owner = 1";
        $sth = $sourceDB->prepare($sql);
        $sth->execute(array($productId));

        $result = $sth->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * @param $block
     * @param $content
     * @return string
     */
    public function appendBlockAtStart($block, $content): string
    {
        $extra = '<!-- wp:bigbite/multi-title --><section class="wp-block-bigbite-multi-title"><div class="container"></div></section><!-- /wp:bigbite/multi-title -->';
        $initStart = 0;
        preg_match(
            '/<!-- \/wp:bigbite\/multi-title -->/s',
            $content,
            $multiLineMatches,
            PREG_OFFSET_CAPTURE,
            0
        );
        if (count($multiLineMatches) > 0) {
            $initStart = $multiLineMatches[0][1]+strlen($multiLineMatches[0][0]);
            $innerHtmlString = substr($content, $initStart, strlen($content));
            return $extra.$block.$this->dataFilter->sanitizeJSON($innerHtmlString);
        }
        return $content;
    }

    /**
     * @param $id <p>post id</p>
     * @param $value <p>integer value to set for postmeta 'is_transformed' key.</p>
     * @return mixed
     */
    public function updateTransformedMetaInfo($id, $value)
    {
        require_once GlobalConstant::$WP_SETUP;
        require_once GlobalConstant::$WP_POST_PATH;
        // transform_history

        // Update the post into the database
        $result = update_post_meta($id, 'is_transformed', $value);
        return $result;
    }

    /**
     * @param $articleId <p>Post Id having type post</p>
     * @param $productId <p>Post Id having type product</p>
     * @param $editorChoice <p>editor choice</p>
     * @param $rating <p>product rating</p>
     * @param $dateLive <p>article/post date live</p>
     * @param null $loggerObj <p>Monolog logger class object</p>
     * @param string $isSingleReview <p>'yes' for: Single product primary review</p>
     * <p>'no' for: Primary review of more than one product</p>
     */
    public function addReviewMetaInfo(
        $articleId,
        $productId,
        $editorChoice,
        $rating,
        $dateLive,
        $loggerObj = null,
        $isSingleReview = 'no'
    ) {
        // prepare 'reviews' array for product's postmeta
        $productReview = get_post_meta($productId, 'reviews', true);
        $reviewArr = $productReview ? json_decode($productReview, true) : [];
        $reviewArr[$articleId] = [
            "type" => "primary",
            "timestamp" => strtotime($dateLive),
            "primary" => (int)$productId,
            "editors_choice" => $editorChoice,
            "rating" => (float)$rating,
            "publication" => get_the_terms($articleId, 'publication')
        ];

        $result = update_post_meta(
            $productId,
            'reviews',
            wp_slash(
                json_encode(
                    $reviewArr,
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
                )
            )
        );
        $loggerObj->info(
            sprintf(
                '----set the reviews postmeta for product {%d} with result {%s}----------',
                $productId,
                json_encode($result)
            )
        );
        // prepare 'reviews' array for article's postmeta
        $articleReview = get_post_meta($articleId, 'reviews', true);
        if (empty($articleReview) || $articleReview === '[]') {
            $newProductValue = "[.$productId.]";
            $artResult = update_post_meta($articleId, 'reviews', $newProductValue);
            $loggerObj->info(
                sprintf(
                    '----set the reviews postmeta for post {%d} with result {%s}----------',
                    $articleId,
                    json_encode($artResult)
                )
            );
        } else {
            $articleReviewArr = json_decode($articleReview);
            if (!in_array($productId, $articleReviewArr)) {
                array_push($articleReviewArr, (int)$productId);
                $artResult =
                    update_post_meta($articleId, 'reviews', json_encode($articleReviewArr));
                $loggerObj->info(
                    sprintf(
                        '----set the reviews postmeta for post {%d} with result {%s}----------',
                        $articleId,
                        json_encode($artResult)
                    )
                );
            } else {
                $loggerObj->info(
                    sprintf('----Id present in reviews in postmeta for product {%d}----------', $articleId)
                );
            }
        }
        // transform_history
        $historyKey = '';
        if ($isSingleReview === 'yes') {
            $historyKey = 'IsReviewTransformed';
        } elseif ($isSingleReview === 'no') {
            $historyKey = 'IsMultiReviewTransformed';
        }
        $this->addTransformHistory($articleId, $historyKey, $loggerObj);
    }

    /**
     * @param $articleId <p>Post Id having type post</p>
     * @param $historyKey <p>History key which we need to set</p>
     * @param null $loggerObj <p>Monolog logger class object</p>
     */
    public function addTransformHistory($articleId, $historyKey, $loggerObj = null)
    {
        $newHistory = [];
        $currentHistory = get_post_meta($articleId, 'transform_history', true);
        if ($currentHistory !== '') {
            $newHistory = unserialize($currentHistory);
        } else {
            $newHistory = GlobalConfig::$TRANSFORM_HISTORY;
        }
        $newHistory[$historyKey] = true;
        $artResult = update_post_meta($articleId, 'transform_history', $newHistory);
        $loggerObj->info(
            sprintf(
                '----set the transform_history postmeta for post {%d} with result {%s}----------',
                $articleId,
                json_encode($artResult)
            )
        );
    }

    public function getPostContentWithoutIsTransformed($queryLimit, $queryOffset)
    {
        require_once GlobalConstant::$WP_SETUP;
        global $wpdb;
        $extraString = '';
        $extraString .= $queryLimit !== '' ? ' limit '.$queryLimit : '';
        $extraString .= $queryOffset !== '' ? ' offset '.$queryOffset : '';
        $articleIds = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT
                    post.ID
                FROM
                    ".$wpdb->prefix."posts as post
                Inner Join
                    ".$wpdb->prefix."postmeta as meta
                ON
                    meta.post_id = post.ID
                WHERE
                    post.post_type = %s
                AND
                    meta.meta_key = %s
                AND
                    meta.meta_value >= %d
                order by
                    post.ID asc"
                .$extraString,
                'post',
                'is_transformed',
                1
            )
        );
        $articleIds = implode(',', $articleIds);

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    post.ID
                FROM
                    ".$wpdb->prefix."posts as post
                WHERE
                    post.post_type = %s
                AND
                    post.ID NOT IN (%1s)
                order by
                    post.ID asc".$extraString,
                'post',
                $articleIds
            )
        );
    }

    /**
     * @return array
     */
    public function getPostIdsToDelete()
    {
        require_once GlobalConstant::$WP_SETUP;
        global $wpdb;
        $postIds = GlobalConfig::$PRODUCTS_TO_DELETE;
        $sql = $wpdb->prepare(
            'SELECT post_id, old_id_in_onecms
                FROM migration_post_mapping
                WHERE
                post_id IN (%1s)',
            $postIds
        );
        $results = $wpdb->get_results($sql);

        return $results;
    }


    /**
     * @param mixed $loggerObj
     *
     * @return
     */
    public function deletePostsFromMappingTable($loggerObj)
    {
        require_once GlobalConstant::$WP_SETUP;
        global $wpdb;
        $postIds = GlobalConfig::$PRODUCTS_TO_DELETE;

        $success = $wpdb->query(
                    "DELETE FROM migration_post_mapping WHERE post_id IN (".
                    $postIds.")"
                );

        $loggerObj->info(
            sprintf(
                'post ids deleted from migration_post_mapping table - {%s}',
                $postIds
            )
        );

        return;
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    public function getAllOneCMSIds($type = 'post')
    {
        require_once GlobalConstant::$WP_SETUP;
        global $wpdb;
        $extraCondition = '';

        if ($type == 'video') {
            $extraCondition = " AND ".$wpdb->prefix."posts.post_mime_type = 'video/mp4'";
            $type = 'attachment';
        }

        $sql = "SELECT meta_value FROM ".$wpdb->prefix."posts
            inner join ".$wpdb->prefix."postmeta ON ".$wpdb->prefix."posts.id = ".$wpdb->prefix."postmeta.post_id
            WHERE post_type = %s and ".$wpdb->prefix."postmeta.meta_key = 'old_id_in_onecms'
            ".$extraCondition."
            and post_status != 'trash'
            order by ".$wpdb->prefix."posts.ID asc";

        return $wpdb->get_results(
            $wpdb->prepare(
                $sql,
                array($type)
            )
        );
    }

    /**
     * @param $oldCMSIds
     * @return array
     */
    public function getAccessArticles($oldCMSIds): array
    {
        $result = [];
        $sourceDB = $this->connectSourceDB();
        if (get_class($sourceDB) !== 'PDOException') {
            $sourceSql = "select id from article where id IN (".$oldCMSIds.") and statusid IN (15,7)";
            $sth = $sourceDB->prepare($sourceSql);
            $sth->execute();
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        } else {
            echo "Source Database is not connected.".PHP_EOL;
            exit;
        }
        return $result;
    }

    /**
     * @param $post_id
     * @param null $loggerObj
     * @return int
     */
    public function updateArticleStatus($post_id, $loggerObj = null): int
    {
        require_once GlobalConstant::$WP_SETUP;
        $postObj = array(
            'ID'           => $post_id,
            'post_status'   => 'trash'
        );
        // Update the post into the database
        $result = wp_update_post($postObj);
        if (is_wp_error($result) && $loggerObj !== null) {
            $this->logger->error(
                sprintf(' --{%d}-- '.$result->get_error_message(), $post_id)
            );
            return 0;
        } else {
            return $result;
        }
    }

    /**
     * @param $oldCMSIds
     * @return array
     */
    public function getAccessProducts($oldCMSIds): array
    {
        $sql = "select id from product
                where id in (".$oldCMSIds.")
                and id not in (
                    SELECT DISTINCT
                    product.id AS product_id
                FROM
                    product
                LEFT JOIN
                    product_vendor_direct
                ON
                    product.id = product_vendor_direct.product_id
                INNER JOIN
                    products_articles
                ON
                    product.id = products_articles.product_id
                INNER JOIN
                    article
                ON
                    products_articles.article_id = article.id
                    AND article.brand_owner = 1 AND article.statusid NOT IN(15, 7)
                WHERE
                    product.product_display_id IN(1, 2) AND product.status = 6
                GROUP BY
                    product.id,
                    product_vendor_direct.product_id
                ORDER BY
                    product.id)";

        $sourceDB = $this->connectSourceDB();

        return $sourceDB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $oldCMSIds
     * @return array
     */
    public function getAccessVideos($oldCMSIds): array
    {
        $sql = "select id from media
                where id in (".$oldCMSIds.")
                and id not in
                (SELECT
                    distinct media.id
                FROM
                    media
                INNER JOIN
                    media_transcode
                ON
                    media.id = media_transcode.media_id
                WHERE
                    media.is_video = TRUE AND media.cms_workflow_status_id = 6
                GROUP BY
                    media.id,
                    media_transcode.status_date,
                    media_transcode.width,
                    media_transcode.height,
                    media_transcode.jw_player_id,
                    media_transcode.notification_email
                ORDER BY
                    media.id
                DESC)";
        $sourceDB = $this->connectSourceDB();

        return $sourceDB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessAttachments($oldCMSIds): array
    {
        $sql = "select id from image
                where id in (".$oldCMSIds.")
                and id not in
                (SELECT
                    distinct image.id
                FROM
                    image
                INNER JOIN
                    image_file
                ON
                    image.id = image_file.image_id
                WHERE
                    image_file.image_type_id = 16
                GROUP BY
                    image.id,
                    image_file.url,
                    image_file.width,
                    image_file.height
                ORDER BY
                    image.id
                DESC)";
        $sourceDB = $this->connectSourceDB();

        return $sourceDB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * @param null $loggerObj
     * @return array
     */
    public function getDeltaBylineFromOneCMS($loggerObj = null): array
    {
        $sourceDB = $this->connectSourceDB();
        $newArray = [];
        if (get_class($sourceDB) !== 'PDOException') {
            $sourceSql = "select a.id as article_id,
                            array_to_string(array_agg(DISTINCT p.name),', ') AS name, ab.byline
                        from person_content_int pci
                        join article a on pci.content_id=a.id and pci.content_type_id=1
                        join person p on pci.person_id=p.id
                        left join article_byline ab on a.id=ab.article_id
                        where a.brand_owner=1 and a.statusid=6
                        and pci.person_id not in (
                        SELECT
                          cms_user.person_id
                        FROM
                          cms_user
                          INNER JOIN person ON cms_user.person_id = person.id
                        WHERE
                          (cms_user.brand_permissions & 1) > 0
                          AND cms_user.username != '' and cms_user.person_id is not null
                        ) GROUP BY a.id, ab.byline";
            $sth = $sourceDB->prepare($sourceSql);
            $sth->execute();

            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            $loggerObj->info(
                sprintf('Total records fetched from source {%s}', count($result))
            );
            $newArray = $this->appendNewPostIdsInArray($result);
            $loggerObj->info(
                sprintf('Total records present in destination {%s}', count($newArray))
            );
            return $newArray;
        } else {
            echo "Source Database is not connected.".PHP_EOL;
        }
        return $newArray;
    }

    /**
     * @param $result
     * @return array
     */
    public function appendNewPostIdsInArray($result): array
    {
        $newArray = [];
        $i=0;
        $j=0;
        foreach ($result as $item) {
            // fetch new post_id by old article ID
            $newValueArr = $this->getNewPostIdByOldIdWithByline($item['article_id']);
            if (count($newValueArr) > 0) {
                $newArray[$i] = $item;
                $newArray[$i]['newArticleId'] = $newValueArr['post_id'];
                $newArray[$i]['existing_byline'] = $newValueArr['meta_value'];
                $i++;
            }
        }
        return $newArray;
    }

    /**
     * @param $oldId
     * @param string $postType
     * @return mixed
     */
    public function getNewPostIdByOldIdWithByline($oldId, $postType = 'post')
    {
        require_once GlobalConstant::$WP_SETUP;
        if ($oldId > 0) {
            global $wpdb;
            $sql = 'SELECT mpm.post_id,pm.meta_value FROM migration_post_mapping as mpm
                    LEFT JOIN '.$wpdb->prefix.'postmeta as pm
                    ON
                    mpm.post_id = pm.post_id and pm.meta_key = "byline"
                    WHERE type = %s AND old_id_in_onecms = %d';
            return $wpdb->get_row($wpdb->prepare($sql, array($postType, $oldId)), ARRAY_A);
        }
        return;
    }

    /**
     * @param $postId
     * @param $bylineValue
     * @param $loggerObj
     */
    public function updateArticleByLine($postId, $bylineValue, $loggerObj = null)
    {
        require_once GlobalConstant::$WP_SETUP;
        // Update the post into the database
        $result = update_post_meta($postId, 'byline', $bylineValue, false);
        $loggerObj->info(
            sprintf(' ---- Postmeta update for {%d} with result {%d}-- ', $postId, $result)
        );
    }
}

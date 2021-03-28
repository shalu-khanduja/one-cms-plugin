<?php

namespace IDG2Migration\util;

use IDG2Migration\config\GlobalConfig;
use IDG2Migration\config\GlobalConstant;
use Monolog\Logger;

require_once GlobalConstant::$WP_SETUP;
require_once GlobalConstant::$WP_TAXONOMY_PATH;

class ContentTypeUtil
{
    private static array $referenceColumns =
        ['category', 'post_tag', 'story_types',
            'sponsorships', 'blogs','podcast_series',
            'workflow_status', 'article_type', 'publication',
            'manufacturer', 'territory','origin', 'vendor_code', 'asset_tag', 'asset_image_rights'];

    /**
     * @param mixed $oldIds
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param int $isUpdate
     * @return array
     */
    public static function articleCategoryLinkMapping(
        $oldIds,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $isUpdate = false
    ) {
        $data = [];
        $termArray = [];
        if (!empty($oldIds) || $isUpdate === true) {
            $catIds = $oldIds;
            // $oldIds may contain repetitive ids, hence making it unique
            if ($oldIds !== '') {
                $explodedArr = explode(',', $oldIds);
                $uniqueIds = array_unique($explodedArr);
                $catIds = implode(',', $uniqueIds);
            }
            $taxonomy = GlobalConfig::$TAXONOMY['CATEGORY'];
            $data = self::fetchTermIdsByOldIds($catIds, $taxonomy);
            if ((!empty($data) || $isUpdate === true) && $isInsert === "on") {
                $termArray = array_values($data);
                /**
                 * Extra manual entry in postmeta
                 */
                self::addPostMeta($postId, $termArray, '_idg_post_categories', $catIds, $loggerObj, $oldCMSId);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $catIds, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param mixed $oldIds
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param int $oldCMSId
     *
     * @return array
     */
    public static function articleTagsLinkMapping(
        $oldIds,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $oldCMSId
    ) {
        $data = [];
        if (!empty($oldIds)) {
            $taxonomy = GlobalConfig::$TAXONOMY['TAGS'];
            $data = self::fetchTermIdsByOldIds($oldIds, $taxonomy);
            if (!empty($data) && $isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldIds, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param mixed $oldIds
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param int $oldCMSId
     *
     * @return array
     */
    public static function articleStoryTypeLinkMapping(
        $oldIds,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $oldCMSId
    ) {
        $data = [];
        if (!empty($oldIds)) {
            $taxonomy = GlobalConfig::$TAXONOMY['STORY_TYPE'];
            $data = self::fetchTermIdsByOldIds($oldIds, $taxonomy);
            if (!empty($data) && $isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldIds, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param mixed $oldIds
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param int $oldCMSId
     *
     * @return array
     */
    public static function articleSponsorshipLinkMapping(
        $oldIds,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $oldCMSId
    ) {
        $data = [];
        if (!empty($oldIds)) {
            $taxonomy = GlobalConfig::$TAXONOMY['SPONSORSHIP'];
            $data = self::fetchTermIdsByOldIds($oldIds, $taxonomy);
            if (!empty($data) && $isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldIds, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param mixed $oldIds
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param int $oldCMSId
     *
     * @return array
     */
    public static function articleBlogMapping(
        $oldIds,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $oldCMSId
    ) {
        $data = [];
        if (!empty($oldIds)) {
            $taxonomy = GlobalConfig::$TAXONOMY['BLOGS'];
            $data = self::fetchTermIdsByOldIds($oldIds, $taxonomy);
            if (!empty($data) && $isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldIds, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param mixed $oldIds
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param int $oldCMSId
     *
     * @return array
     */
    public static function articlePodcastMapping(
        $oldIds,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $oldCMSId
    ) {
        $data = [];
        if (!empty($oldIds)) {
            $taxonomy = GlobalConfig::$TAXONOMY['PODCAST'];
            $data = self::fetchTermIdsByOldIds($oldIds, $taxonomy);
            if (!empty($data) && $isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldIds, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param mixed $oldIds
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param bool $isSingle
     * @param int $oldCMSId
     * @param int $metaKey
     * @return array|int
     */
    public static function authorMapping(
        $oldIds,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $isSingle = false,
        $oldCMSId = '',
        $metaKey = 'post_author'
    ) {
        global $wpdb;
        $data = [];

        if (!empty($oldIds)) {
            if ($isSingle) {
                $idArr = explode(',', $oldIds);
                $oldIds = $idArr[0];
            }

            $result = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT us.ID, meta.meta_value
                        FROM '.$wpdb->prefix.'users as us
                        INNER JOIN '.$wpdb->prefix.'usermeta as meta
                        ON us.ID = meta.user_id
                        WHERE
                        meta.meta_key = "old_id_in_onecms"
                        AND meta.meta_value IN (%1s)',
                    $oldIds
                )
            );

            if (count($result) > 0) {
                foreach ($result as $value) {
                    if ($isSingle === true) {
                        return $value->ID;
                    }
                    $data[$value->meta_value] = $value->ID;
                }
            }
            if (count($result) === 0 && $isInsert === "off") {
                return 0;
            }
            if ($isInsert === "on" && $isSingle === false) {
                $termArray = array_values($data);
                $data = self::addPostMeta(
                    $postId,
                    array_map('intval', $termArray),
                    $metaKey,
                    $oldIds,
                    $loggerObj,
                    $oldCMSId
                );
            }
        } else {
            if ($isInsert === "on" && $isSingle === false) {
                $termArray = [];
                $data = self::addPostMeta(
                    $postId,
                    $termArray,
                    $metaKey,
                    $oldIds,
                    $loggerObj,
                    $oldCMSId
                );
            } elseif ($isSingle === true) {
                return 0;
            }
        }
        return $data;
    }

    /**
     * @param mixed $oldIds
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param int $oldCMSId
     * @param int $addLimit
     *
     * @return array
     */
    public static function imageMapping(
        $oldIds,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $oldCMSId = '',
        $addLimit = 0
    ) {
        global $wpdb;
        $limit = $addLimit > 0 ? 'LIMIT '.$addLimit.' OFFSET 0' : '';
        $data = [];
        if (!empty($oldIds)) {
            $sql = $wpdb->prepare(
                'SELECT post_id, old_id_in_onecms
                    FROM migration_post_mapping
                    WHERE
                    type = "attachment"
                    AND old_id_in_onecms IN (%1s) %1s',
                $oldIds,
                $limit
            );
            $results = $wpdb->get_results($sql);

            if (count($results) > 0) {
                foreach ($results as $value) {
                    $data[$value->old_id_in_onecms] = $value->post_id;
                }
            }
            if ($isInsert === "on") {
                $termArray = implode(',', $data);
                $data = self::addPostMeta(
                    $postId,
                    $termArray,
                    '_thumbnail_id',
                    $oldIds,
                    $loggerObj,
                    $oldCMSId
                );
            }
        } else {
            if ($isInsert === "on") {
                $termArray = '';
                $data = self::addPostMeta($postId, $termArray, '_thumbnail_id', $oldIds, $loggerObj, $oldCMSId);
            }
        }
        return $data;
    }

    /**
     * @param string $oldIds
     * @param string $taxonomy
     *
     * @return array
     */
    public static function fetchTermIdsByOldIds($oldIds, $taxonomy)
    {
        global $wpdb;
        $data = array();

        if (!empty($oldIds) && !empty($taxonomy)) {
            $orderByForIn = '';
            // extra condition to preserve selection order based on the order of ids present in query's IN clause
            if ($taxonomy === GlobalConfig::$TAXONOMY['CATEGORY']) {
                $orderByForIn = 'ORDER BY FIELD(old_id_in_onecms, '.$oldIds.')';
            }
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT term_id, old_id_in_onecms as meta_value
                        FROM migration_term_mapping
                        WHERE
                        old_id_in_onecms IN (%1s)
                        AND taxonomy_type = %s %1s',
                    $oldIds,
                    $taxonomy,
                    $orderByForIn
                )
            );

            if (count($results) > 0) {
                foreach ($results as $value) {
                    $data[$value->meta_value] = $value->term_id;
                }
            }
        }

        return $data;
    }

    /**
     * @param $postID
     * @param $termId
     * @param $termTaxonomy
     * @param $idString
     * @param null $loggerObj
     * @param string $oldCMSId
     *
     * @return array
     */
    public static function addPostTerm($postID, $termId, $termTaxonomy, $idString, $loggerObj = null, $oldCMSId = '')
    {
        $taxonomyIds = wp_set_post_terms($postID, array_map('intval', $termId), $termTaxonomy);
        if (!is_wp_error($taxonomyIds)) {
            if ($loggerObj) {
                self::logReferenceInfoInLoggerForSeed(
                    $postID,
                    $termTaxonomy,
                    implode(',', $taxonomyIds),
                    $idString,
                    $loggerObj,
                    $oldCMSId
                );
            }

            return $taxonomyIds;
        } else {
            if ($loggerObj) {
                self::logReferenceInfoInLoggerForSeed(
                    $postID,
                    $termTaxonomy,
                    0,
                    $idString,
                    $loggerObj,
                    $oldCMSId
                );
            }

            return 0;
        }
    }

    /**
     * @param mixed $termId
     * @param mixed $key
     * @param mixed $itm
     * @param mixed $oldCMSId
     * @param null $loggerObj
     *
     */
    public static function logReferenceInfoInLogger($termId, $key, $itm, $oldCMSId, $loggerObj = null)
    {
        if (in_array($key, self::$referenceColumns)) {
            if (strpos($itm, 'ref_error') !== false) {
                $errArray = explode('|', $itm);
                $loggerObj->error(
                    sprintf(
                        '{%s}-{%d} Reference NOT found {%s} with id {%d}',
                        $oldCMSId,
                        $termId,
                        $key,
                        $errArray[1]
                    )
                );
            } else {
                $loggerObj->info(
                    sprintf(
                        '{%s}-{%d} Reference of {%s} change to {%s} now ',
                        $oldCMSId,
                        $termId,
                        $key,
                        $itm
                    )
                );
            }
        }
    }

    /**
     * @param $postID
     * @param mixed $key
     * @param $newIds
     * @param $idString
     * @param null $loggerObj
     * @param string $oldCMSId
     */
    public static function logReferenceInfoInLoggerForSeed(
        $postID,
        $key,
        $newIds,
        $idString,
        $loggerObj = null,
        $oldCMSId = ''
    ) {
        if (in_array($key, self::$referenceColumns)) {
            if (strpos($newIds, 'ref_error') !== false) {
                $errArray = explode('|', $newIds);
                $loggerObj->error(
                    sprintf(
                        '{%s}-{%d} Reference NOT found {%s} with id {%d}',
                        $oldCMSId,
                        $postID,
                        $key,
                        $errArray[1]
                    )
                );
            } else {
                $loggerObj->info(
                    sprintf(
                        '{%s}-{%d} Reference of {%s} change {%s} to {%s} now ',
                        $oldCMSId,
                        $postID,
                        $key,
                        json_encode($idString),
                        $newIds
                    )
                );
            }
        }
    }


    /**
     * @param $article_status_slug
     * @param string $isInsert
     * @param int $oldCMSId
     * @param int $postId
     * @param object $loggerObj
     *
     * @return array
     */
    public static function articleWorkflowStatusLinkMapping(
        $article_status_slug,
        $isInsert = "off",
        $oldCMSId = 0,
        $postId = 0,
        Logger $loggerObj = null
    ) {
        $result = [];
        if (!empty($article_status_slug)) {
            $workflow_status_mapping_array = GlobalConfig::$WORKFLOW_STATUS;
            $article_status_slug = strtolower($article_status_slug);
            $new_workflow_status = $workflow_status_mapping_array[$article_status_slug];
            if ($new_workflow_status !== null) {
                $result['new_status'] = $new_workflow_status;
            } else {
                $result['new_status'] = "ref_error";
            }
            if ($result['new_status'] !== 'ref_error') {
                $loggerObj->info(sprintf(
                    '{%d}-{%d} Workflow meta status found {%s}',
                    $oldCMSId,
                    $postId,
                    $result['new_status']
                ));
            } else {
                $loggerObj->error(sprintf(
                    '{%d}-{%d} Workflow meta status not updated {%s}',
                    $oldCMSId,
                    $postId,
                    $result['new_status']
                ));
            }
        }
        return $result;
    }

    /**
     * @param string $key
     * @param string $meta_value
     * @param int $oldCMSId
     * @param int $postId
     * @param object $loggerObj
     *
     * @return array
     */
    public static function addPostMetaData($key, $meta_value, $oldCMSId, $postId, Logger $loggerObj)
    {
        if (!self::metadataExists('post', $postId, $key)) {
            $result = add_post_meta($postId, $key, $meta_value, false);
            $logger_action = GlobalConfig::$LOGGER_KEYS['insert'];
        } else {
            $result = update_post_meta($postId, $key, $meta_value, '');
            $logger_action = GlobalConfig::$LOGGER_KEYS['update'];
        }
        self::logReferenceInfoInLogger($postId, $key, $meta_value, $oldCMSId, $loggerObj);

        $loggerObj->info(
            sprintf(
                '{%d}-{%d} Action {%s} postmeta -post id- {%d} -meta key- {%s} -keyvalue- {%s}',
                $oldCMSId,
                $postId,
                $logger_action,
                $postId,
                $key,
                $meta_value
            )
        );
        $loggerObj->info(sprintf('{%d}-{%d} Result {%s}', $oldCMSId, $postId, $result));
        return $result;
    }

    /**
     * @param $metaType
     * @param $objectId
     * @param $metaKey
     *
     * @return bool
     */
    public static function metadataExists($metaType, $objectId, $metaKey): bool
    {
        return metadata_exists($metaType, $objectId, $metaKey);
    }


    /**
     * Migration mapping to get article type term_id from article_bug_id and
     * article_display_id and update postmeta _idg_updated_flag
     * when article_bug_id to 1.
     *
     * @param int $idg_bug_id article_bug_id
     * @param  int $idg_display_id article_display_id
     * @param string $isInsert
     * @param int $oldCMSId
     * @param int $postId This filed required for update post meta
     * @param object $loggerObj
     *
     * @return array $result with the result
     */
    public static function articleArticleTypeLinkMapping(
        $idg_bug_id = 0,
        $idg_display_id = 0,
        $isInsert = "off",
        $oldCMSId = 0,
        $postId = 0,
        Logger $loggerObj = null
    ): array {
        $article_bug_array = GlobalConfig::$ARTICLE_BUG;
        $article_display_array = GlobalConfig::$ARTICLE_DISPLAY;
        $oldId = 0;
        $result = [];
        if ($idg_bug_id > 0) {
            if ($idg_bug_id === 1) {
                //update the Post meta: _idg_updated_flag to true
                if ($postId > 0) {
                    $key = "_idg_updated_flag";
                    $meta_value = true;
                    self::addPostMetaData($key, $meta_value, $oldCMSId, $postId, $loggerObj);
                    $result['is_meta_update'] = true;
                }
            } else {
                //If Matching found in Article bug list
                if (array_key_exists($idg_bug_id, $article_bug_array)) {
                    $oldId = $idg_bug_id;
                }
            }
        }
        if ($idg_display_id > 0 && $oldId  === 0) {
            //If Matching not found in Article bug list but found in article diplay
            if (array_key_exists($idg_display_id, $article_display_array)) {
                $oldId = $idg_display_id;
            }
        }
        //Map to default article_display type when no matching found
        if ($oldId === 0) {
            $oldId = 1;
        }
        $taxonomy = GlobalConfig::$TAXONOMY['ARTICLE_TYPE'];
        $data = self::fetchTermIdsByOldIds($oldId, $taxonomy);

        if (!empty($data) && $isInsert === "on") {
            $termArray = array_values($data);
            $result['updated_term_id'] =
                self::addPostTerm(
                    $postId,
                    $termArray,
                    $taxonomy,
                    $oldId,
                    $loggerObj,
                    $oldCMSId
                );
        }
        return $result;
    }

    /**
     * @param $postID
     * @param $termArr
     * @param $metaKey
     * @param $idString
     * @param null $loggerObj
     * @param $oldCMSId
     * @return int
     */
    public static function addPostMeta($postID, $termArr, $metaKey, $idString, $loggerObj = null, $oldCMSId)
    {
        global $wpdb;
        $result = update_post_meta($postID, $metaKey, $termArr);
        $mId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s",
                array( $postID, $metaKey )
            )
        );
        $loggerObj->info(
            sprintf(
                '{%s}-{%d} Action {%s} postmeta -post id- {%d} -meta key- {%s} -value- changed from {%s} to {%s}',
                $oldCMSId,
                $postID,
                $result ? GlobalConfig::$LOGGER_KEYS['insert'] : GlobalConfig::$LOGGER_KEYS['update'],
                $postID,
                $metaKey,
                $idString,
                is_array($termArr) ? implode(",", $termArr) : $termArr
            )
        );

        return $mId;
    }

    /**
     * @param $value
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param  Logger $loggerObj
     */
    public static function addArticleBusinessUnitLink(
        $value,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null
    ) {
        if (!empty($value)) {
            $taxonomy = GlobalConfig::$TAXONOMY['BUSINESS_UNIT'];
            if ($isInsert === "on") {
                $data = self::addPostTerm($postId, $value, $taxonomy, $value, $loggerObj, $oldCMSId);
            }
        }
    }

    /**
     * @param $value
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param Logger|null $loggerObj
     */
    public static function addArticlePublicationLink(
        $value,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null
    ) {
        if (!empty($value)) {
            $taxonomy = GlobalConfig::$TAXONOMY['PUBLICATION'];
            if ($isInsert === "on") {
                $data = self::addPostTerm($postId, $value, $taxonomy, $value, $loggerObj, $oldCMSId);
            }
        }
    }

    /**
     * @param mixed $oldIds
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param bool $isUpdate
     *
     * @return array
     */
    public static function productMaufactureLinkMapping(
        $oldIds,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $isUpdate = false
    ) {
        $data = [];
        if (!empty($oldIds) || $isUpdate === true) {
            $taxonomy = GlobalConfig::$TAXONOMY['MANUFACTURER'];
            $data = self::fetchTermIdsByOldIds($oldIds, $taxonomy);
            if ($isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldIds, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }



    /**
     * @param string $oldSlug
     * @param string $taxonomy
     *
     * @return array
     */
    public static function fetchTermIdsByOldSlug($oldSlug, $taxonomy)
    {
        global $wpdb;
        $data = array();

        if (!empty($oldSlug) && !empty($taxonomy)) {
            $sql = $wpdb->prepare(
                "SELECT tm.term_id
                FROM  $wpdb->terms as tm
                INNER JOIN  $wpdb->term_taxonomy as tt
                ON tm.term_id = tt.term_id
                WHERE
                tm.slug = '%s'
                AND tt.taxonomy = '%1s'",
                $oldSlug,
                $taxonomy
            );

            $results = $wpdb->get_results($sql);

            if (count($results) > 0) {
                foreach ($results as $value) {
                    $data[] = $value->term_id;
                }
            }
        }

        return $data;
    }

    /**
     * @param string $oldSlug
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param bool $isUpdate
     *
     * @return array
     */
    public static function productTerritoriesLinkMapping(
        $oldSlug,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $isUpdate = false
    ) {
        $data = [];
        $oldSlug = 'us'; //Set default to US
        if (!empty($oldSlug) || $isUpdate === true) {
            $taxonomy = GlobalConfig::$TAXONOMY['TERRITORY'];
            $data = self::fetchTermIdsByOldSlug($oldSlug, $taxonomy);
            if ($isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldSlug, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param string $oldSlug
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param bool $isUpdate
     *
     * @return array
     */
    public static function productCmsOriginLinkMapping(
        $oldSlug,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $isUpdate = false
    ) {
        $data = [];
        $oldSlug = 'onecms';
        if (!empty($oldSlug) || $isUpdate === true) {
            $taxonomy = GlobalConfig::$TAXONOMY['ORIGIN'];
            $data = self::fetchTermIdsByOldSlug($oldSlug, $taxonomy);
            if ($isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldSlug, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param int $vendor_code
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param bool $isUpdate
     *
     * @return array
     */
    public static function productVendorCodeLinkMapping(
        $vendor_code,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $isUpdate = false
    ) {
        $data = [];
        $oldSlug = ($vendor_code === 2) ? 'amazon' : '';

        if (!empty($oldSlug) || $isUpdate === true) {
            $taxonomy = GlobalConfig::$TAXONOMY['VENDOR_CODE'];
            $data = self::fetchTermIdsByOldSlug($oldSlug, $taxonomy);
            if ($isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $vendor_code, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param $multiRegionInfoArray
     * @param $productTitle
     */
    public static function generateRegionInfoArray($multiRegionInfoArray, $productTitle = '')
    {
        $productDefaultRegion = "US";
        $productPriceCurrency = "USD";
        $appendManufacturer = false;
        $productPrice = '';
        $productPrice = $multiRegionInfoArray['price']['value'];
        $appendManufacturer
            = $multiRegionInfoArray['prepend_company_title']['value'];
        $pricingProviderId
            = $multiRegionInfoArray['pricing_provider_id']['value'];
        $providerProductSlug = ($pricingProviderId === 2) ? 'amazon' : '';
        $vendorCode = $multiRegionInfoArray['provider_product_id']['value'];

        $data = [];
        $data = (object)array(
            $productDefaultRegion => (object)array(
                'pricing' => (object)array(
                    'currency' => $productPriceCurrency,
                    'price_options' => '',
                    'price' => $productPrice
                ),
                'product_info' => (object)array(
                    'name' => $productTitle,
                    'append_manufacturer' => $appendManufacturer,
                ),
            ),
        );

        if (!empty($vendorCode) || !empty($providerProductSlug)) {
            $purchase_options =  (object)array(
                'vendor_codes' => array((object)array(
                    'vendor' => $providerProductSlug,
                    'code' => $vendorCode
                )));
            $data->$productDefaultRegion->purchase_options = $purchase_options;
        }

        return wp_slash(json_encode($data));
    }

    /**
     * @param $multiRegionInfoArray
     * @param $postTitle
     */
    public static function generateGlobalInfoArray($multiGlobalInfoArray)
    {
        $productDefaultRegion = "";
        $productDefaultCurrency = "USD";

        $product_globalinfo_object = (object)array();
        foreach ($multiGlobalInfoArray as $key => $value) {
            $vendorText = $value['product_vendor_direct_text'];
            $vendorLink = $value['product_vendor_direct_link'];
            $vendorPrice = $value['product_vendor_direct_price'];

            if (!empty($vendorText) ||
                !empty($vendorLink) ||
                !empty($vendorPrice)
            ) {
                $vendor_links[$key] = (object)array(
                    'vendor' => $vendorText,
                    'territory' => $productDefaultRegion,
                    'url' => $vendorLink,
                    'price' => $vendorPrice,
                    'currency' =>  $productDefaultCurrency,
                );
            }
        }
        $josonData = '';
        if (isset($vendor_links)) {
            $product_globalinfo_object->purchase_options->vendor_links
            = $vendor_links;
            $josonData = json_encode($product_globalinfo_object);
        }
        return wp_slash($josonData);
    }

    /**
     * TODO: TO Be deprecated in future release
     * @param mixed $oldArticleIds
     * @param int $oldProductId
     * @param string $isInsert
     * @param int $newProductId
     * @param object $loggerObj
     * @param bool $isUpdate
     * @return array
     */
    public static function productArticlesLinkMapping(
        $oldArticleIds,
        $oldProductId,
        $isInsert = "off",
        $newProductId = 0,
        Logger $loggerObj = null,
        $isUpdate = false
    ) {
        $metaKey = 'reviews';
        $oldArticles = [];
        if ((!empty($newProductId) && !empty($oldArticleIds)) || $isUpdate == true) {
            $oldArticles = self::extractArticleIds($oldArticleIds);
            // update product refrence in article
            $articleIds = self::addProductRefrenceInArticle($oldArticles, $metaKey, $newProductId, $loggerObj);
            // update article refrence in product
            self::addArticleRefrenceInProduct($oldProductId, $newProductId, $metaKey, $articleIds, $loggerObj);
        }
    }

    /**
     * @param mixed $oldArticleIds
     *
     * @return array
     */
    public static function extractArticleIds($oldArticleIds)
    {
        $oldArticles = [];
        $oldArticleTypeArr = explode(',', $oldArticleIds);
        foreach ($oldArticleTypeArr as $value) {
            $articleIdArr = explode('|', $value);
            $oldArticles[] = trim($articleIdArr[0]);
        }

        return $oldArticles;
    }

    /**
     * @param mixed $oldArticles
     * @param mixed $metaKey
     * @param mixed $newProductId
     * @param mixed $loggerObj
     *
     * @return array
     */
    public static function addProductRefrenceInArticle($oldArticles, $metaKey, $newProductId, $loggerObj)
    {
        global $wpdb;
        $articleIds = [];
        $oldArticlesStr = implode(',', $oldArticles);
        $postIdArr = [];

        if ($oldArticlesStr !== '') {
            $postIdArr = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT post_id, meta_key, meta_value
                FROM $wpdb->postmeta
                WHERE meta_key = '%s' AND
                post_id IN (
                    SELECT post_id
                    FROM $wpdb->postmeta
                    WHERE meta_value IN (%1s)
                    AND meta_key = 'old_id_in_onecms')",
                    $metaKey,
                    $oldArticlesStr
                )
            );
        }

        foreach ($postIdArr as $value) {
            $articleIds[] = $value->post_id;
            if (empty($value->meta_value) || $value->meta_value === '[]') {
                $newProductValue = "[".$newProductId."]";
                $result = update_post_meta($value->post_id, $metaKey, $newProductValue);
            } else {
                $newProductValue = str_replace(']', ','.$newProductId.']', $value->meta_value);
                $result = update_post_meta($value->post_id, $metaKey, $newProductValue);
            }
            self::addLogForProductReviews(
                [
                    'oldId' => $oldArticles,
                    'newId' => $value->post_id,
                    'metaKey' => $metaKey,
                    'newValue' => $newProductValue,
                    'result' => $result,
                    'type' => 'Product'
                ],
                $loggerObj
            );
        }

        return $articleIds;
    }

    /**
     * @param mixed $data
     * @param mixed $loggerObj
     *
     */
    public static function addLogForProductReviews($data, Logger $loggerObj = null)
    {
        $loggerObj->info(
            sprintf(
                '{%s}-{%s} {%s} Reference of {%s} changed to {%s} now ',
                is_array($data['oldId']) ?  implode(',', $data['oldId']) : $data['oldId'],
                $data['newId'],
                $data['type'],
                $data['metaKey'],
                $data['newValue']
            )
        );
        $oldIDs = '';
        $newIds = '';
        if (is_array($data['oldId'])) {
            $oldIDs = implode(',', $data['oldId']);
        }
        if (is_array($data['newId'])) {
            $newIds = implode(',', $data['newId']);
        }

        $loggerObj->info(
            sprintf(
                '{%s}-{%s} Result {%s}',
                $oldIDs,
                $newIds,
                $data['result']
            )
        );
    }

    /**
     * @param mixed $oldProductId
     * @param mixed $newProductId
     * @param mixed $metaKey
     * @param mixed $articleIds
     * @param Logger|null $loggerObj
     *
     */
    public static function addArticleRefrenceInProduct($oldProductId, $newProductId, $metaKey, $articleIds, Logger $loggerObj = null)
    {
        $productReview = get_post_meta($newProductId, $metaKey, true);
        $reviewArr = $productReview ? json_decode($productReview, true) : [];
        foreach ($articleIds as $value) {
            $reviewArr[$value] = [
                'type' => 'comparison',
                'timestamp' => time(),
                'primary' => $newProductId,
                'publication' =>  false, // need to change after discussion
                'comparison' => "", // need to change after discussion
                // 'editors_choice' => false, // need to change after discussion
                //'rating' => 5 // need to change after discussion
            ];
        }
        $result = update_post_meta($newProductId, $metaKey, json_encode($reviewArr));
        self::addLogForProductReviews(
            [
                'oldId' => $oldProductId,
                'newId' => $newProductId,
                'metaKey' => $metaKey,
                'newValue' =>json_encode($reviewArr),
                'result' => $result,
                'type' => 'Article'
            ],
            $loggerObj
        );
    }

    /**
     * @param mixed $oldIds
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     *
     * @return array
     */
    public static function attachmentAssetsTagLinkMapping(
        $oldIds,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null
    ) {
        $data = [];
        if (!empty($oldIds)) {
            $taxonomy = GlobalConfig::$TAXONOMY['ASSETSTAG'];
            $data = self::fetchTermIdsByOldIds($oldIds, $taxonomy);
            if ($isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldIds, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param mixed $oldIds
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     *
     * @return array
     */
    public static function attachmentAssetsImageRightsLinkMapping(
        $oldIds,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null
    ) {
        $data = [];
        if (!empty($oldIds)) {
            $taxonomy = GlobalConfig::$TAXONOMY['ASSETSIMAGERIGHTS'];
            $data = self::fetchTermIdsByOldIds($oldIds, $taxonomy);
            if ($isInsert === "on") {
                $termArray = array_values($data);
                $data = self::addPostTerm($postId, $termArray, $taxonomy, $oldIds, $loggerObj, $oldCMSId);
            }
        }

        return $data;
    }

    /**
     * @param mixed $globalInfovalues
     * @param int $oldCMSId
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param bool $isUpdate
     *
     * @return array
     */
    public static function globalInfoLinkMapping(
        $globalInfovalues,
        $oldCMSId,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null
    ) {
        if (!empty($globalInfovalues)) {
            $globalInfoArray = explode(' ||,|| ', $globalInfovalues);
            $globalInfoValueMultiArray = [];
            foreach ($globalInfoArray as $key => $value) {
                $globalInfoValueArray = explode(' *|* ', $value);
                $globalInfoValueMultiArray[$key]['product_vendor_direct_text']
                    = $globalInfoValueArray[1];
                $globalInfoValueMultiArray[$key]['product_vendor_direct_link']
                    = $globalInfoValueArray[2];
                $globalInfoValueMultiArray[$key]['product_vendor_direct_price']
                    = $globalInfoValueArray[3];
            }
            $json_data  = ContentTypeUtil::generateGlobalInfoArray($globalInfoValueMultiArray);

            if ((!empty($json_data)) && $isInsert === "on") {
                /**
                 * Extra manual entry in postmeta
                 */
                self::addPostMeta($postId, $json_data, 'global_info', $globalInfovalues, $loggerObj, $oldCMSId);
            }
        }
    }

    /**
     * @param mixed $oldIds
     * @param string $isInsert
     * @param int $postId
     * @param object $loggerObj
     * @param bool $isSingle
     * @param int $oldCMSId
     * @param int $metaKey
     * @return array|int
     */
    public static function videoMapping(
        $oldIds,
        $isInsert = "off",
        $postId = 0,
        Logger $loggerObj = null,
        $isSingle = false,
        $oldCMSId = '',
        $metaKey = 'featured_video_id'
    ) {
        global $wpdb;
        $data = [];
        if (!empty($oldIds)) {
            if ($isSingle) {
                $idArr = explode(',', $oldIds);
                $oldIds = $idArr[0];
            }
            $sql = $wpdb->prepare(
                'SELECT
                    post_id, old_id_in_onecms
                FROM
                    migration_post_mapping
                WHERE
                    type = "video"
                AND
                    old_id_in_onecms = %s',
                $oldIds
            );
            $results = $wpdb->get_results($sql);

            if (count($results) > 0) {
                foreach ($results as $value) {
                    $data[$value->old_id_in_onecms] = $value->post_id;
                }
            }
            if ($isInsert === "on") {
                $termArray = implode(',', $data);
                $data = self::addPostMeta(
                    $postId,
                    $termArray,
                    $metaKey,
                    $oldIds,
                    $loggerObj,
                    $oldCMSId
                );
            }
        } else {
            if ($isInsert === "on") {
                $termArray = '';
                $data = self::addPostMeta($postId, $termArray, $metaKey, $oldIds, $loggerObj, $oldCMSId);
            }
        }
        return $data;
    }
}

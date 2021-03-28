<?php


namespace IDG2Migration\repository;

use IDG2Migration\config\GlobalConstant;

class IngesterRepository
{
    public function __construct()
    {
    }

    /**
     * @param $postType
     * @param $oneCMSId
     * @return int
     */
    public function postExists($postType, $oneCMSId): int
    {
        require_once GlobalConstant::$WP_SETUP;
        require_once GlobalConstant::$WP_TAXONOMY_PATH;
        global $wpdb;
        $sql =<<<SQL
        SELECT post_id
        FROM migration_post_mapping
          WHERE
          old_id_in_onecms IN ($oneCMSId) AND type = '$postType'
SQL;
        $result = $wpdb->get_results($wpdb->prepare($sql));

        if (count($result) > 0) {
            return $result[0]->post_id;
        }
        return 0;
    }


    /**
     * @param $metaType
     * @param $objectId
     * @param $metaKey
     *
     * @return bool
     */
    public function isMetaDataExists($metaType, $objectId, $metaKey): bool
    {
        require_once GlobalConstant::$WP_SETUP;
        require_once GlobalConstant::$WP_TAXONOMY_PATH;
        return metadata_exists($metaType, $objectId, $metaKey);
    }

    /**
     * @param $termMetaObject
     * @param $termId
     * @param $originalId
     * @param $customKey
     *
     * @return string[]
     */
    public function injectExtraValueInMetaObject($termMetaObject, $termId, $originalId, $customKey): array
    {
        if ($originalId !== '' && $termId !== '') {
            return $termMetaObject + [$customKey => $originalId.'-'.$termId];
        } elseif ($originalId !== '' && $termId === '') {
            return $termMetaObject + [$customKey => $originalId];
        } elseif ($originalId === '' && $termId !== '') {
            return $termMetaObject + [$customKey => $termId];
        } else {
            return $termMetaObject + [$customKey => ''];
        }

        return $termMetaObject;
    }

    /**
     * @return int
     */
    public function getBusinessUnit()
    {
        require_once GlobalConstant::$WP_SETUP;
        global $wpdb;
        $termId = '';

        $sql = 'SELECT wp_terms.term_id as tid FROM wp_termmeta
               inner join wp_terms on wp_terms.term_id = wp_termmeta.term_id
               WHERE meta_key = "publication_type"
               and meta_value = "business-unit"
               order by wp_terms.term_id ASC
               limit 1';
        $results = $wpdb->get_results($sql);

        if (count($results) > 0) {
            $termId = $results[0]->tid;
        }

        return $termId;
    }

    /**
     * @return int
     */
    public function getPublication()
    {
        require_once GlobalConstant::$WP_SETUP;
        global $wpdb;
        $termId = '';

        $sql = 'SELECT wp_terms.term_id as tid FROM wp_termmeta
               inner join wp_terms on wp_terms.term_id = wp_termmeta.term_id
               WHERE meta_key = "publication_type"
               and meta_value = "publication"
               order by wp_terms.term_id ASC
               limit 1';

        $results = $wpdb->get_results($sql);

        if (count($results) > 0) {
            $termId = $results[0]->tid;
        }

        return $termId;
    }

    public function logExecutedSqlQuery($loggerObj)
    {
        if (isset($_SESSION['executed_sql'])) {
            $executed_sql = $_SESSION['executed_sql'];
            $loggerObj->info(
                sprintf('SQL Query Executed :{%s}', $executed_sql)
            );
            unset($_SESSION['executed_sql']);
        }
    }

    /**
     * @param $postID
     * @param $oneCMSId
     * @param $postType
     * @param $loggerObj
     */
    public function insertPostMappingSqlQuery($postID, $oneCMSId, $postType, $loggerObj)
    {
        global $wpdb;
        $executed_sql =<<<SQL
        INSERT into  migration_post_mapping set
        post_id = $postID, old_id_in_onecms = $oneCMSId, type = '$postType'
SQL;
        $mapping_result = $wpdb->get_results($wpdb->prepare($executed_sql));

        $loggerObj->info(
            sprintf('SQL Query Executed for mapping:{%s}', $executed_sql)
        );
    }
}

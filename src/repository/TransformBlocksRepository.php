<?php


namespace IDG2Migration\repository;

use IDG2Migration\config\GlobalConstant;
use IDG2Migration\db\SourceConnection;
use PDO;
use PDOException;

class TransformBlocksRepository
{
    /**
     * @param $queryParam
     * @return mixed
     */
    public function getAllPosts($queryParam): array
    {
        require_once GlobalConstant::$WP_SETUP;
        $result = [];
        $limit = $queryParam['limit'] > 0 ? $queryParam['limit'] : -1;
        $offset = $queryParam['offset'] > 0 ? $queryParam['offset'] : 0;
        $order = $queryParam['order'] !== '' ? $queryParam['order'] : 'DESC ';
        $defaults = array(
            'numberposts'      => $limit,
            'offset'           => $offset,
            'orderby'          => 'ID',
            'order'            => $order,
            'post_type'        => 'post',
            'post_status'      => array(
                                    'publish-ready',
                                    'on-hold',
                                    'review-ready',
                                    'publish',
                                    'pending',
                                    'draft',
                                    'auto-draft',
                                    'future',
                                    'private',
                                    'inherit',
                                    'trash',
                                    'updated'
                                ),
            'meta_key'         => 'is_transformed',
            'meta_value'       => 0,
            'meta_compare'     => '='
        );
        $postData = get_posts($defaults);
        for ($i=0; $i < count($postData); $i++) {
            $result[$i]['post'] = $postData[$i];
            $result[$i]['postmeta'] = get_post_meta($postData[$i]->ID);
        }
        return $result;
    }

    /**
     * @param $postDataToUpdate
     * @return mixed
     */
    public function updateContentWithTransformedContent($postDataToUpdate)
    {
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        $result = wp_update_post($postDataToUpdate, true);
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        return $result;
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param $metaValue
     * @return mixed
     */
    public function updatePostMetaForTransformedContent($postId, $metaKey, $metaValue)
    {
        return update_post_meta($postId, $metaKey, $metaValue);
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

    /**
     * @param $articleId
     * @return array
     */
    public function fetchProductsFromSourceByArticle($articleId): array
    {
        $productDetailsArray = [];
        $sourceDB = $this->connectSourceDB();
        if (get_class($sourceDB) !== 'PDOException' && $articleId !== '') {
            $sourceSql = "SELECT
                            products_articles.product_id,
                            array_to_string(array_agg(DISTINCT(CONCAT( phco.highlight, '|DO|', phco.display_order))), '|HI|') as con,
                            array_to_string(array_agg(DISTINCT(CONCAT( phpo.highlight, '|DO|', phpo.display_order))), '|HI|') as pro,
                            product_edit_info.blurb,
                            product_edit_info.rating,
                            array_to_string(array_agg(product_edit_info.chart_summary), '|$|') as chart_summary
                        FROM
                                products_articles
                        LEFT JOIN product_highlight as phco
                        ON products_articles.product_id = phco.product_id
                        AND (phco.brand_display_perms & 1) > 0
                        AND phco.product_highlight_type_id = 1
                        LEFT JOIN product_highlight as phpo
                        ON products_articles.product_id = phpo.product_id
                        AND (phpo.brand_display_perms & 1) > 0
                        AND phpo.product_highlight_type_id = 2
                        LEFT JOIN product_edit_info
                        ON products_articles.product_id = product_edit_info.product_id
                        AND product_edit_info.brand_owner = 1
                        WHERE
                          article_id IN (".$articleId.")
                        AND ((products_articles.is_capsule & 1) > 0 OR products_articles.is_capsule = 0)
                        GROUP BY products_articles.product_id, phco.product_id, phpo.product_id, product_edit_info.product_id,
                        product_edit_info.blurb, product_edit_info.rating";
            $sth = $sourceDB->prepare($sourceSql);
            $sth->execute();

            /* Fetch all of the remaining rows in the result set */
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } else {
            echo "Source Database is not connected.".PHP_EOL;
            exit;
        }
        return $productDetailsArray;
    }

    /**
     * @param $productId
     * @param null $sourceDB
     * @return mixed
     */
    public function fetchProductEditInfo($productId, $sourceDB = null)
    {
        $sourceSql = 'SELECT
                *
                FROM
                    product_edit_info
                WHERE product_edit_info.product_id = '. $productId .'
                AND product_edit_info.brand_owner = 1
            ';
        $sth = $sourceDB->prepare($sourceSql);
        $sth->execute();

        /* Fetch all of the remaining rows in the result set */
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $productId
     * @param null $sourceDB
     * @return mixed
     */
    public function fetchProductHighlightInfo($productId, $sourceDB = null)
    {
        $sourceSql = 'SELECT
                *
                FROM
                    product_highlight
                WHERE product_highlight.product_id = '. $productId .'
                AND (product_highlight.brand_display_perms & 1) > 0
            ';
        $sth = $sourceDB->prepare($sourceSql);
        $sth->execute();

        /* Fetch all of the remaining rows in the result set */
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $oldProductId
     * @return mixed
     */
    public function fetchNewProductInfo($oldProductId)
    {
        if ($oldProductId > 0) {
            global $wpdb;
            $sql = 'SELECT
                    post_id, tt.term_id as manufacture_Id
                    FROM
                        '.$wpdb->prefix.'posts as post
                    INNER JOIN '.$wpdb->prefix.'postmeta as meta
                        ON meta.post_id = post.ID
                    AND
                        post.post_type = "product"
                    INNER JOIN '.$wpdb->prefix.'term_relationships as tr
                        ON tr.object_id = post.ID
                    INNER JOIN '.$wpdb->prefix.'term_taxonomy as tt
                        ON tt.term_id = tr.term_taxonomy_id
                    AND
                        tt.taxonomy = "manufacturer"
                    WHERE
                        meta.meta_key = "old_id_in_onecms"
                    AND
                        meta.meta_value = %s';

            return $wpdb->get_row($wpdb->prepare($sql, array($oldProductId)), ARRAY_A);
        }
    }

    /**
     * @param $oldVideoId
     * @return mixed
     */
    public function fetchVideoJWPlayerId($oldVideoId)
    {
        if ($oldVideoId > 0) {
            global $wpdb;
            $sql = 'SELECT
                        meta.meta_value as mediaId
                    FROM
                        '.$wpdb->prefix.'postmeta as meta
                    WHERE
                        meta.meta_key = "jw_player_media_id"
                    AND
                        meta.post_id = (SELECT post_id
                    FROM
                        migration_post_mapping
                    WHERE
                        type = "video"
                    AND
                        old_id_in_onecms = %s)';
            return $wpdb->get_row($wpdb->prepare($sql, array($oldVideoId)), ARRAY_A);
        }
    }
}

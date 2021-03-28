<?php


namespace IDG2Migration\modules;

use IDG2Migration\helpers\DataFilter;
use IDG2Migration\repository\TransformBlocksRepository;
use Monolog\Logger;

class ContentModificationModule
{
    /**
     * @var TransformBlocksRepository
     */
    private TransformBlocksRepository $transformRepository;
    /**
     * @var DataFilter
     */
    private DataFilter $dataFilter;

    /**
     * ContentModificationModule constructor.
     */
    public function __construct()
    {
        $this->dataFilter = new DataFilter();
        $this->transformRepository = new TransformBlocksRepository();
    }

    /**
     * @param $queryParam
     * @return mixed
     */
    public function getPosts($queryParam)
    {
        return $this->transformRepository->getAllPosts($queryParam);
    }

    /**
     * @param $postId
     * @param $finalContent
     * @return mixed
     */
    public function saveUpdatedContent($postId, $finalContent)
    {
        $postDataToUpdate = array(
            'ID'           => $postId,
            'post_content' => $finalContent,
        );
        return $this->transformRepository->updateContentWithTransformedContent($postDataToUpdate);
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param $metaValue
     * @param $logger
     * @return mixed
     */
    public function savePostMetaContent($postId, $metaKey, $metaValue, $logger)
    {
        $result = $this->transformRepository->updatePostMetaForTransformedContent($postId, $metaKey, $metaValue);
        $logger->info(
            sprintf(
                '----set the %s postmeta for post {%d} with result {%s}----------',
                $metaKey,
                $postId,
                json_encode($result)
            )
        );
    }

    /**
     * @param $articleId
     * @return array
     */
    public function getProductDetailsFromSource($articleId): array
    {
        $result = [];
        $productDetailsArray = [];
        if ($articleId > 0) {
            $result = $this->transformRepository->fetchProductsFromSourceByArticle($articleId);
            if (count($result) > 0) {
                foreach ($result as $item) {
                    // fetch details of product_edit_info
                    $productDetailsArray[$item['product_id']]['product_info'] = $item;
                    $productDetailsArray[$item['product_id']]['new_product_info'] =
                        $this->transformRepository->fetchNewProductInfo($item['product_id']);
                }
                return $productDetailsArray;
            }
        }
        return $result;
    }

    /**
     * @param $mainString
     * @param $productArray
     * @param $logger
     * @param $transformHistory
     * @return string
     */
    public function productLinkBlock($mainString, $productArray, $logger, &$transformHistory):string
    {
        preg_match_all(
            '/<a class="productLink.*?data-productid="([^"]+)".*?>.[^<]+<\/a>/',
            $mainString,
            $matches,
            PREG_OFFSET_CAPTURE,
            0
        );
        for ($i = 0; $i < count($matches[0]); $i++) {
            $oldProductId = $matches[1][$i][0];
            $oldProductAttribue = 'data-productid="'.$oldProductId.'"';
            $productLinkblock = str_replace('productLink', 'product-link', $matches[0][$i][0]);
            $transformHistory['IsProductLinkTransformed'] = true;
            $newProductId = $productArray[$oldProductId]['new_product_info']['post_id'];
            if ($newProductId != '') {
                $productManufacturer = $productArray[$oldProductId]['new_product_info']['manufacture_Id'];
                $newProductAttribue = 'data-product="' . $newProductId.'" ';
                $newProductAttribue .= 'data-manufacturer="' . $productManufacturer . '" ';
                $productLinkblock = str_replace($oldProductAttribue, $newProductAttribue, $productLinkblock);
                $logger->info(
                    sprintf(
                        '----Content string has been converted from {%s} to {%s}----------',
                        $matches[0][$i][0],
                        $productLinkblock
                    )
                );
            } else {
                $newProductAttribue = 'data-product="0" ';
                $newProductAttribue .= 'data-manufacturer="0" ';
                $productLinkblock = str_replace($oldProductAttribue, $newProductAttribue, $productLinkblock);
                $logger->error(
                    sprintf(
                        '----Product not found for ID --{%s}-- ----------',
                        $oldProductId
                    )
                );
            }
            $mainString = str_replace($matches[0][$i][0], $productLinkblock, $mainString);
        }
        return $mainString;
    }
    /**
     * @param string $stringToManipulate
     * @param array $productArray
     * @param object $logger
     * @param $transformHistory
     *
     * @return string
     */
    public function getProductChartBlock($stringToManipulate, $productArray, Logger $logger, &$transformHistory)
    {
        $data =  $this->parseHtmlAndGetProductDetails($stringToManipulate);
        $allBlocks = $this->prepareChartBlock($data, $productArray, $logger);
        $productChartBlock = $this->composeAllBlocks($allBlocks);
        $transformHistory['IsProductChartTransformed'] = true;

        return $productChartBlock;
    }

    /**
     * @param string $stringToManipulate
     *
     * @return array
     */
    public function parseHtmlAndGetProductDetails($stringToManipulate)
    {
        preg_match_all(
            '/<a.*data-productid="([^"]+).*<\/a>/',
            $stringToManipulate,
            $allProducts,
            PREG_OFFSET_CAPTURE,
            0
        );

        preg_match_all(
            '/<ol class=".*numbering">/s',
            $stringToManipulate,
            $numberingClass,
            PREG_UNMATCHED_AS_NULL,
            0
        );

        if (!empty($allProducts[1])) {
            foreach ($allProducts[1] as $key => $value) {
                $productIds[$value[0]] = ['rank' => ($key+1)];
            }
        }

        return [
            'oldProductData' => $productIds,
            'isShowingRank' => (!empty($numberingClass[0][0]) ? true : false)
        ];
    }

    /**
     * @param array $data
     * @param array $productArray
     * @param object $logger
     *
     * @return array
     */
    public function prepareChartBlock($data, $productArray, Logger $logger)
    {
        $logger->info(
            sprintf(
                '--- product chart block transformation started ---'
            )
        );
        foreach ($data['oldProductData'] as $key => $value) {
            if (!empty($productArray[$key]['new_product_info']['post_id'])) {
                $productContent = "";
                if (!empty($productArray[$key]['product_info']['chart_summary'])) {
                    $productContentArr = explode("|$|", $productArray[$key]['product_info']['chart_summary']);
                    $productContent = $productContentArr[0];
                }
                $rating = !empty($productArray[$key]['product_info']['rating'])
                    ?
                    ($productArray[$key]['product_info']['rating'])
                    : 0;

                $chartBlockArr = [
                    'rank' => $value['rank'] ?? 0,
                    'productId' => (int) $productArray[$key]['new_product_info']['post_id'],
                    'titleOverride' => false,
                    'productContent' => $productContent,
                    'productRating' => (float) $rating,
                    'ratingOverride' => ($rating != 0) ? true : false,
                    'productImageSize' => 'small',
                    'productImage' => 0,
                    'imageFromOrigin' => true,
                    'productTitle' => ''
                ];

                $productChartBlock['productData'][] = $chartBlockArr;

                $productDetails = [
                    'rank' => $value['rank'] ?? 0,
                    'productId' => (int) $productArray[$key]['new_product_info']['post_id'],
                    'productContent' => $productContent,
                ];

                if ($rating != 0) {
                    $productDetails['productRating'] = (float) $rating;
                    $productDetails['ratingOverride'] = true;
                }

                $chartItems.= '<!-- wp:idg-base-theme/product-chart-item '
                    .trim(wp_slash(json_encode($productDetails, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)), '[]').' /-->';

                unset($chartBlockArr['productContent']);
                $logger->info(
                    sprintf(
                        '{%d}-{%d} product-chart-block values {%s}',
                        $key,
                        $productArray[$key]['new_product_info']['post_id'],
                        json_encode($chartBlockArr)
                    )
                );

                $logger->info(
                    sprintf(
                        '{%d}-{%d} product attachment done for product id {%d}',
                        $key,
                        $productArray[$key]['new_product_info']['post_id'],
                        $productArray[$key]['new_product_info']['post_id']
                    )
                );
            } else {
                $logger->error(
                    sprintf('{%d} product id missing in wordpress.', $key)
                );
            }
        }

        $logger->info(
            sprintf(
                '--- product chart block transformation ended ---'
            )
        );

        $productChartBlock['isShowingRank'] = $data['isShowingRank'];
        $productChartBlock['linksInNewTab'] = true;

        return [
            'productChartBlock' => (!empty($productChartBlock) ? wp_slash(json_encode($productChartBlock, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)) : ''),
            'chartItems' => $chartItems
        ];
    }

    /**
     * @param array $allBlocks
     *
     * @return string
     */
    public function composeAllBlocks($allBlocks)
    {
        $finalBlock = '<!-- wp:idg-base-theme/product-chart-block '.
            $allBlocks['productChartBlock'].' -->'.
            '<div class="wp-block-idg-base-theme-product-chart-block wp-block-product-chart product-chart">'.
            $allBlocks['chartItems'].'</div><!-- /wp:idg-base-theme/product-chart-block -->';

        return $finalBlock;
    }

    /*
     * @param $mainString
     * @param $productArray
     * @param $logger
     * @param $transformHistory
     * @return mixed
     */
    public function sideBarBlock($mainString, $productArray, $logger, &$transformHistory)
    {
        $data = [];
        $titlePattern = '/<h6 class="nav-title">(.*?)<\/h6>/s';
        preg_match_all($titlePattern, $mainString, $matches, PREG_OFFSET_CAPTURE, 0);
        $produtIdPattern='/<a.*class=".*product-name.*".*data-productid="([^"]+)".*>.*<\/a>/s';
        preg_match_all($produtIdPattern, $mainString, $idMatches);
        $fullwidthPattern = '/<aside class=".*? fullwidth *">.*?<\/aside>/s';

        $transformHistory['IsProductWidgetTransformed'] = true;
        $data['productId'] = (int)$productArray[$idMatches[1][0]]['new_product_info']['post_id'];
        if ($data['productId'] > 0) {
            if(!empty(str_replace(" ", '', $matches[1][0][0]))){
                $data['blockTitle'] = $matches[1][0][0];
            }
            if (!preg_match($fullwidthPattern, $mainString)) {
                $data['isHalfWidth'] = true;
                $data['isFloatRight'] = true;
            }

            $sideBarBlog = '<!-- wp:idg-base-theme/product-widget-block ';
            $sideBarBlog .= json_encode($data);
            $sideBarBlog .= ' /-->';
            $logger->info(
                sprintf(
                    '----Content string has been coverted from {%s} to {%s}----------',
                    $mainString,
                    $sideBarBlog
                )
            );
            return $sideBarBlog;
        } else {
            $logger->error(
                sprintf(
                    '----Product not found for the ID -- {%s} ----------',
                    $idMatches[1][0],
                )
            );
            return '<!-- wp:idg-base-theme/product-widget-block  /-->';
        }
    }

    /*
     * @param $mainString
     * @param $logger
     * @param $transformHistory
     * @return mixed
     */
    public function videoModificationBlock($mainString, $logger, &$transformHistory)
    {
        $videoIdPattern = '/<video.*id="vid([^"]+)".*>.*<\/video>/s';
        preg_match_all($videoIdPattern, $mainString, $idMatches);
        $data = $this->transformRepository->fetchVideoJWPlayerId($idMatches[1][0]);
        $transformHistory['IsVideoTransformed'] = true;
        if (!empty($data) && $data['mediaId'] != null) {
            $videoBlock = '<!-- wp:idg-base-theme/jwplayer ';
            $videoBlock .= json_encode($data);
            $videoBlock .= ' /-->';
            $logger->info(
                sprintf(
                    '----Content string has been coverted from {%s} to {%s}----------',
                    $mainString,
                    $videoBlock
                )
            );

            return $videoBlock;
        } else {
            $logger->error(
                sprintf(
                    '----jwplayer id not found for the ID -- {%s} ----------',
                    $idMatches[1][0],
                )
            );

            return;
        }
    }
}

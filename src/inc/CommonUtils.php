<?php
namespace IDG2Migration\inc;

use IDG2Migration\config\GlobalConfig;

use WP_CLI;

class CommonUtils {

     /**
     * Flagged parameter variables 
     */
    protected $_sub_command;
    protected $_script_param;
    protected $_brand_id = 0;
    protected $_limit = 0;
    protected $_offset = 0;
    protected $_sleep_const = [];
    protected $_is_delta=0;
    protected $_delta_date;
    protected $_config_site="default";
    protected $_assoc_parm_arr=array();
    

    public function __construct() {
    }
   
    protected function parse_params($params) {
        $this->_limit =  (int) trim($params['limit']);
        $this->_offset = (int) trim($params['offset']);
        $this->_brand_id= (int) trim($params['brand-id']);
        $this->_is_delta= trim($params['is-delta']);
        $this->_delta_date= trim($params['delta-date']);
        $this->_config_site= trim($params['site']);
        $this->_sleep_const['SLEEP_THRESHOLD'] = (int) trim($params['sleep-records']);
        $this->_sleep_const['SLEEP_FOR_SECONDS'] = (int) trim($params['sleep-seconds']);
        
        $this->validate();
    }

    protected function validate(){

         /** validation for limit*/
         if($this->_limit <0){
            WP_CLI::error('Please provide integer options with greater than 0 for limit ');
            die;
         }
        
         
        /** validation for offset*/
        if($this->_offset < 0){
            WP_CLI::error('Please provide integer options with greater than 0 for limit ');
            die;
         }
        

        /** validation for sleep-record*/
       
        if($this->_sleep_const['SLEEP_THRESHOLD'] <= 0){
            WP_CLI::error('Please provide integer options with greater than 0 for --sleep-record');
            die;
        }
      
        
        /** validation for sleep-second*/
        
        if($this->_sleep_const['SLEEP_FOR_SECONDS'] <= 0){
            WP_CLI::error('Please provide integer options with greater than 0 for --sleep-seconds');
            die;
        }
      
        
        /** validation for brand id*/
         if($this->_brand_id <= 0){
            WP_CLI::error('Please provide integer options with greater than 0 for  --brand-id');
            die;
        }
      
         /** validation for _is_delta */
        if($this->_is_delta!="on" && $this->_is_delta!="off"){
            WP_CLI::error('Please provide on/off options only for --is-delta');
            die;
        }
         
        /** validation for _config_site */
        if (!preg_match ("/^[a-zA-Z\s]+$/",$this->_config_site) && $this->_config_site!="") {
            WP_CLI::error('Options must only contain letters for --site');
            die;
            
         } 
         
         /** validation _delta_date added */
        if($this->_is_delta=="on" && $this->_delta_date==""){
            WP_CLI::error('Please provide delta_date options ');
            
        }
       
          /** validation for _delta_date */
          if($this->_delta_date!=""){
            if (!preg_match("/^((((19|[2-9]\d)\d{2})\/(0[13578]|1[02])\/(0[1-9]|[12]\d|3[01]))|(((19|[2-9]\d)\d{2})\/(0[13456789]|1[012])\/(0[1-9]|[12]\d|30))|(((19|[2-9]\d)\d{2})\/02\/(0[1-9]|1\d|2[0-8]))|(((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))\/02\/29))$/",$this->_delta_date)) {
                WP_CLI::error('Options must use this (Format-YYYY/MM/dd) for --delta-date');
                die;
                } 
            }
           
            /** Associative array */
           
               $this->_assoc_parm_arr=array(
                "limit"=>$this->_limit,
                "offset"=>$this->_offset,
                "brand-id"=>$this->_brand_id,
                "is-delta"=>$this->_is_delta,
                "delta-date"=>$this->_delta_date,
                "site"=>$this->_config_site,
                "sleep-records"=>$this->_sleep_const['SLEEP_THRESHOLD'],
                "sleep-seconds"=>$this->_sleep_const['SLEEP_FOR_SECONDS']
               );
             
            
    }

    public function start($argv) {
        try {
                if (in_array('-h', $argv) || in_array('-help', $argv)) {
                    echo sprintf(
                        "========== \033[32mHelp document for script execution\033[0m =========="."\n".
                        "Parameters List =>"."\n".
                        "First parameter =>      \033[31m(Required)\033[0m Key to execute specific script"."\n".
                        "Second parameter  =>      \033[32m(Optional)\033[0m Limit to fetch records from source database.
                                                Possible value -> Any integer greater than 0
                                                Default value  -> 0"."\n".
                        "Third parameter =>      \033[32m(Optional)\033[0m Offset while fetching records from source database.
                                                Possible value -> Any integer greater than or equal to 0
                                                Default value  -> 0"."\n".
                        "Fourth parameter  =>      \033[32m(Optional)\033[0m Number of records after which you want to put execution in sleep mode.
                                                Possible value -> Any integer greater than 0
                                                Default value  -> %d"."\n".
                        "Fifth parameter  =>      \033[32m(Optional)\033[0m Number pf seconds for which you want to put execution in sleep mode.
                                                Possible value -> Any integer greater than 0
                                                Default value  -> %d"."\n".
                        "\033[31mNOTE: IF YOU WANT TO USE ANY PARAMETER IT'S PREVIOUS PARAMS VALUE(ACTUAL/DEFAULT) SHOULD BE PASSED.\033[0m"."\n".
                        "\n",
                        GlobalConfig::$SLEEP['SLEEP_THRESHOLD'],
                        GlobalConfig::$SLEEP['SLEEP_FOR_SECONDS']
                    );
                    exit;
                }
                $this->parse_params($argv);
                if ($scriptParam !== '') {
                    switch ($scriptParam) {
                        case 'articletype':
                            $articleTypeMigration = new \IDG2Migration\migrations\ArticleTypeMigration($scriptParam);
                            $articleTypeMigration->initMigration($scriptParam);
                            break;
                        case 'storytype':
                            $storyTypeMigration = new \IDG2Migration\migrations\StoryTypeMigration($scriptParam);
                            $storyTypeMigration->initMigration($scriptParam);
                            break;
                        case 'user':
                            $userMigration = new \IDG2Migration\migrations\UserMigration($scriptParam);
                            $userMigration->initMigration($scriptParam);
                            break;
                        case 'business-publication':
                            $businessPublication = new \IDG2Migration\migrations\BusinessPublicationMigration($scriptParam);
                            $businessPublication->initMigration();
                            break;
                        case 'podcast':
                        case 'blog':
                            $blogMigration = new \IDG2Migration\migrations\BlogMigration($scriptParam);
                            $blogMigration->initMigration($scriptParam);
                            break;
                        case 'tag':
                            $blogMigration = new \IDG2Migration\migrations\TagMigration($scriptParam);
                            $blogMigration->initMigration($scriptParam);
                            break;
                        case 'sponsorship':
                            $sponsorshipMigration = new \IDG2Migration\migrations\SponsorshipMigration($scriptParam);
                            $sponsorshipMigration->initMigration($scriptParam);
                            break;
                        case 'imagetag':
                            $imagetagMigration = new \IDG2Migration\migrations\ImageTagMigration($scriptParam);
                            $imagetagMigration->initMigration($scriptParam);
                            break;
                        case 'category':
                            $categoryMigration = new \IDG2Migration\migrations\CategoryMigration($scriptParam);
                            $categoryMigration->initMigration($scriptParam);
                            break;
                        case 'imagerights':
                            $imagerightMigration = new \IDG2Migration\migrations\ImageRightsMigration($scriptParam);
                            $imagerightMigration->initMigration($scriptParam);
                            break;
                        case 'image-blog':
                        case 'image-sponsorship':
                        case 'update-image-author':
                        case 'image-user':
                        case 'attachment':
                            $attachmentMigration = new \IDG2Migration\migrations\ArticleMigration($scriptParam);
                            $attachmentMigration->initMigration($scriptParam, $this->_sleep_const);
                            break;
                        case 'manufactures':
                            $manufacturesMigration = new \IDG2Migration\migrations\ManufacturesMigration($scriptParam);
                            $manufacturesMigration->initMigration($scriptParam);
                            break;
                        case 'product-territory':
                            $productTerritoryMigration = new \IDG2Migration\migrations\ProductTerritoriesMigration($scriptParam);
                            $productTerritoryMigration->initMigration($scriptParam);
                            break;
                        case 'product-vc':
                            $productVc = new \IDG2Migration\migrations\ProductVcMigration($scriptParam);
                            $productVc->initMigration();
                            break;
                        case 'article':
                        case 'article-video':
                        case 'article-audio':
                        case 'article-bug-updated':
                        case 'article-default':
                        case 'article-external-url':
                        case 'article-slideshow':
                        case 'article-product-hub':
                        case 'article-bug-delta':
                        case 'article-display-delta':
                            $articleMigration = new \IDG2Migration\migrations\ArticleMigration($scriptParam);
                            $articleMigration->initMigration($scriptParam, $this->_sleep_const);
                            break;
                        case 'origin-cms':
                            $businessPublication = new \IDG2Migration\migrations\OriginCMS($scriptParam);
                            $businessPublication->initMigration();
                            break;
                        case 'product':
                            $productMigration = new \IDG2Migration\migrations\ArticleMigration($scriptParam);
                            $productMigration->initMigration($scriptParam, $this->_sleep_const);
                            break;
                        case 'filter-wphtml':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->initFilter($this->_limit, $this->_offset);
                            break;
                        case 'video':
                        case 'video-embedded':
                            $videoMigration = new \IDG2Migration\migrations\ArticleMigration($scriptParam);
                            $videoMigration->initMigration($scriptParam, $this->_sleep_const);
                            break;
                        case 'remove-inactive-post-tag':
                            $data = [
                                'taxonomy' => 'post_tag',
                                'queryLimit' => $this->_limit,
                                'queryOffset' => $this->_offset,
                                'name' => 'post_tag',
                            ];
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->initRemoveInactiveTag($data);
                            break;
                        case 'remove-inactive-image-tag':
                            $data = [
                                'taxonomy' => 'asset_tag',
                                'queryLimit' => $this->_limit,
                                'queryOffset' => $this->_offset,
                                'name' => 'image-tag',
                            ];
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->initRemoveInactiveTag($data);
                            break;
                        case 'add-istransformed-to-article':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->setIsTransformed($this->_limit, $this->_offset);
                            break;
                        case 'content-modify':
                            // TODO: all content have wp-html and article
                            $contentModify = new \IDG2Migration\migrations\ContentModification($scriptParam);
                            $queryParam = ['offset' =>  $this->_offset, 'limit' => $this->_limit, 'order' => 'desc'];
                            $contentModify->initMigration($queryParam);
                            break;
                        case 'transform-review':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->initHandleReviews($this->_limit, $this->_offset);
                            break;
                        case 'transform-review-chart':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->initHandleReviewsChats($this->_limit, $this->_offset);
                            break;
                        case 'delete-specific-posts':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->deleteSpecificPosts();
                            break;
                        case 'clean-access-articles':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->cleanAccessArticles();
                            break;
                        case 'clean-access-products':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->cleanAccessType('product');
                            break;
                        case 'clean-access-videos':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->cleanAccessType('video');
                            break;
                        case 'clean-access-attachments':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->cleanAccessType('attachment');
                            break;
                        case 'byline-delta':
                            $filterContent = new \IDG2Migration\migrations\FilterContent($scriptParam);
                            $filterContent->initByLineDelta();
                            break;
                        default:
                        WP_CLI::error('Invalid script type for parsing!');
                    }
                } else {
                    WP_CLI::error('Invalid script type for parsing!');
                }
            } catch (Exception $e) {
                echo $e->getMessage().PHP_EOL;
            }
    }
}
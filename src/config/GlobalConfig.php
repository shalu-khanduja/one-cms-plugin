<?php

namespace IDG2Migration\config;

class GlobalConfig
{
    public static int $ASSIGNED_BUSINESS_UNITS = 1;
    public static string $WP_CAPABILITIES = '';
    public static $USER_PASS = null;
    public static $INSERT_ACTION = 'INSERT';
    public static $UPDATE_ACTION = 'UPDATE';
    public static $SOCIAL_NETOWRK = [
                                        2 => 'twitter',
                                        3 => 'facebook',
                                        4 => 'linkedin',
                                    ];
    public static array $USER_ROLES = [
                                    'SuperAdmins' => 'Administrator',
                                    'SuperAccessDenied' => '',
                                    'IDG SMS Adops' => 'SMS AdOps',
                                    'xtra-B2CHomePageHero' => 'Contributor',
                                    'IDG Freelance Blogger' => 'Contributor',
                                    'IDG Freelance Blogger Publish' => 'Contributor',
                                    'ICN' => '',
                                    'xtra-ICNManager' => '',
                                    'xtra-PostAllSites' => '',
                                    'xtra-CodeEditor' => '',
                                    'IDG Freelance Writer' => 'Author',
                                    'IDG Staff Writer' => 'Author',
                                    'IDG Staff Editor' => 'Editor',
                                    'IDG Managing Editor' => 'Managing Editor',
                                    'IDG Freelance Editor' => 'Editor',
                                    'IDG Site Admin' => 'Administrator',
                                    'IDG DealPost Admin' => 'Editor',
                                    'xtra-ManageDatabaseEditor' => '',
                                    'xtra-CreateAndEditTags' => '',
                                    'xtra-AddPromos' => '',
                                    'IDG LeadGen' => 'Contributor',
                                    'xtra-ManagePromos' => '',
                                    'test_group' => '',
                                    'IDG Custom BrandPost' => 'Editor',
                                    'IDG Video' => 'Editor',
                                    'IDG Designer' => 'Design',
                                    'xtra-Syndication' => '',
                                    'xtra-Products' => '',
                                    'xtra-SlotHomePage' => '',
                                    'xtra-SlotNewsletters' => '',
                                    'IDG DealPost Editor' => 'Contributor',
                                    'IDG Custom Freelance Editor' => 'Editor',
                                    'xtra-UploadImages' => '',
                                    'xtra-AdvancedImageRights' => '',
                                 ];
    public static array $LOGGER_KEYS = [
        'insert' => 'INSERT',
        'update' => 'UPDATE',
        'delete' => 'DELETE',
    ];
    // in case of aggregate encapsulate string in double quotes
    public static array $ALISE_SPECIAL_KEYS = [
        'image_alt_text_meta' => 'image.alt_text as image_alt_text_meta',
        'tag_content_int_tag_id' =>
            "array_to_string(array_agg(DISTINCT tag_content_int.tag_id),',') as tag_content_int_tag_id",
        'tagged_categories' =>
            "Array_to_string(Array_agg(category_content_int.category_id ORDER BY category_content_int.priority ASC), ',') AS tagged_categories",
        'content_license_int_license_id' =>
            "array_to_string(array_agg(DISTINCT content_license_int.license_id),',') as content_license_int_license_id",
        'author_ids' => "array_to_string(array_agg(DISTINCT cms_user.id), ',') as author_ids",
        'tagged_ids' => "array_to_string(array_agg(DISTINCT tag_content_int.tag_id), ',') as tagged_ids",
        'post_excerpt' => "article.summary as post_excerpt",
        'post_name' => "article.slug as post_name",
        '_thumbnail_ids' => "array_to_string(array_agg(DISTINCT image_file.image_id), ',') as _thumbnail_ids",
        'product_article_ids' =>
        "array_to_string(array_agg(DISTINCT CONCAT(products_articles.article_id, ' | ' ,products_articles.insert_type)),', ') as product_article_ids",
        'categories_priority' =>
            "array_to_string(array_agg(DISTINCT category_content_int.priority), ',') as categories_priority",
        'vendor_code' =>
            "product_pricing_provider_int.pricing_provider_id as vendor_code",
        'product_vendor_direct_ids' =>
            "array_to_string(array_agg(DISTINCT CONCAT(product_vendor_direct.id,' *|* ', product_vendor_direct.text, ' *|* ', product_vendor_direct.link, ' *|* ', product_vendor_direct.price)), ' ||,|| ') AS product_vendor_direct_ids",
        'media_alt_text_meta' => 'media.title as media_alt_text_meta',
        'media_author_ids' =>
            "array_to_string(array_agg(DISTINCT cms_user.id),',') as media_author_ids",
        'credit' =>
            "COALESCE(person.name, media.byline, media_transcode.notification_email) as credit",
        'feature_videos_ids' => "array_to_string(array_agg(DISTINCT gallery_member_int.content_id), ',') as feature_videos_ids"
    ];
    public static array $TAXONOMY = [
        'CATEGORY' => 'category',
        'TAGS' => 'post_tag',
        'STORY_TYPE' => 'story_types',
        'SPONSORSHIP' => 'sponsorships',
        'BLOGS' => 'blogs',
        'PODCAST' => 'podcast_series',
        'ARTICLE_TYPE' => 'article_type',
        'BUSINESS_UNIT' => 'publication',
        'PUBLICATION' => 'publication',
        'MANUFACTURER' => 'manufacturer',
        'TERRITORY' => 'territory',
        'ORIGIN' => 'origin',
        'VENDOR_CODE' => 'vendor_code',
        'PRODUCT_CATEGORY' => 'product_category',
        'ASSETSTAG' => 'asset_tag',
        'ASSETSIMAGERIGHTS' => 'asset_image_rights'
    ];
    public static array $WORKFLOW_STATUS = [
        'working' => 'draft',
        'ready to launch' => 'publish-ready',
        'live' => 'publish',
        'killed' => 'draft',
        'archived' => 'draft',
        'ready for review' => 'review-ready',
        'failed' => 'draft',
        'copy edit' => 'draft',
        'proposed' => 'draft',
        'approved' => 'on-hold',
        'newsdesk edit' => 'draft',
        'scheduled' => 'future',
    ];
    public static array $ARTICLE_BUG = [
        6 => 'video',
        17 => 'Audio',
    ];
    public static array $ARTICLE_DISPLAY = [
        1 => 'Default',
        10 => 'external url',
        11 => 'slideshow',
        19 => 'product hub',
    ];
    public static array $TRANSFORM_HISTORY = [
        "IsReviewTransformed" => false,
        "IsReviewChartTransformed" => false,
        "IsProductWidgetTransformed" => false,
        "IsProductLinkTransformed" => false,
        "IsProductChartTransformed" => false,
        "IsPaginationTransformed" => false,
        "IsVideoTransformed" => false,
        "IsMultiReviewTransformed" => false,
    ];
    public static array $SLEEP = [
        "SLEEP_THRESHOLD" => 1000,
        "SLEEP_FOR_SECONDS" => 60 // seconds
    ];
    public static string $PRODUCTS_TO_DELETE = '196915,196916,196917,196918,196919,196924,196945,196952,196955,196991,196993,197110,197133,197165,197247,197387,197401,197412,197413,197414,197415,197416,197730,197731,197732,197733,197734,197735,197736,197737,197753,197756,197759,197765,197775,197798,197809,197859,197863,197864,197868,197871,197882,197889,198024,198025,198026,198027,198059,198060,198064,198065,198078,198079,198080,198081,198082,198092,198108,198118,198119,198120,198121,198124,198125,198126,198129,198169,198170,198173,198174,198175,198176,198177,198219,198256,198267,198268,198269,198270,198274,198275,198276';
    public function __construct()
    {
    }
}

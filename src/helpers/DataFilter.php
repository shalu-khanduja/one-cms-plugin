<?php

namespace IDG2Migration\helpers;
use IDG2Migration\config\GlobalConstant;

class DataFilter
{
    public function __construct()
    {
    }

    /*
     * Helper function to generate column sequence for insert query
     */
    public function generateColumnSeq($paramArr)
    {
        return array_map(function ($param) {
            return "{$param}";
        }, $paramArr);
    }

    /*
     * Helper function to check if the column is present in ignore column list
     */
    public function removeIgnoreColumns($paramArr, $destinationArray)
    {
        $filterArray = [];
        foreach ($paramArr as $key => $item) {
            if (in_array($key, $destinationArray)) {
                $filterArray[$key] = $item;
            }
        }
        return $filterArray;
    }

    public function getValueFromNameFromMappingXML($xmlObj, $attributeName, $type = 'string')
    {
        switch (strtolower($type)) {
            case 'string':
                return isset($xmlObj->attributes()[$attributeName])
                    ? (string)$xmlObj->attributes()[$attributeName] : '';
            case 'boolean':
                if ((string)$xmlObj->attributes()[$attributeName] === 'true') {
                    return (string)$xmlObj->attributes()[$attributeName];
                } else {
                    return false;
                }
            case 'integer':
                return isset($xmlObj->attributes()[$attributeName])
                    ? (int)$xmlObj->attributes()[$attributeName] : '';
        }
        return '';
    }

    /**
     * Genrate slug for duplicate name
     *
     * @param  $name string to genrate the slug
     * @return $orig_slugify string
     */
    public function genrateSlugify($name)
    {
        $orig_slugify = mb_strtolower(preg_replace('/([^A-Za-z0-9]|-)+/', '-', $name));
        return $orig_slugify;
    }

    /**
     * @param $callbackArray
     * @param bool $appendExtra
     * @return mixed
     */
    public function sanitizePostContent($callbackArray, $appendExtra = false): string
    {
        $cleanedContent = $this->removeStartAndEndPart($callbackArray['value']);
        // will add content filter rules here
        if ($appendExtra === true) {
            ob_start();?>
            <!-- wp:bigbite/multi-title -->
            <section class="wp-block-bigbite-multi-title"><div class="container"></div></section>
            <!-- /wp:bigbite/multi-title --><?php
            return ob_get_clean().$cleanedContent;
        }
        return $cleanedContent;
    }


    /**
     * @param string $type
     *
     * @return string
     */
    public function getDirectory($type)
    {
        $logPath = GlobalConstant::$LOG_DIR.date('d-m-Y', time()).'/'.$type.'/';
        if (!file_exists($logPath)) {
            if (!mkdir($logPath, 0755, true)) {
                die('Failed to create log folder');
            }
            chmod($logPath, 0755);
        }

        return $logPath;
    }

    /**
     * @param $contentString
     * @return string
     */
    public function removeStartAndEndPart($contentString): string
    {
        // TODO: need to validate new start and end pattern and also check /s or /m delimiters
        $reStart = '/<article>[\n|\s]*<section class="page">|<article>(.*)/s';
        $reEnd = '/<\/section>[\n|\s]*<section class="page"\/>[\n|\s]*<\/article>|<\/section>[\n|\s]*<\/article>|<\/article>$/s';
        // remove article and section from start
        $strWithRemovedStart = preg_replace($reStart, '', $contentString);
        // remove article and section from end
        $strWithRemovedEnd = preg_replace($reEnd, '', $strWithRemovedStart);
        $strWithRemovedEnd = trim($strWithRemovedEnd);
        return $strWithRemovedEnd;
    }

    /**
     * @param $content <p>html/content having json</p>
     * @return string|string[]|null
     */
    public function sanitizeJSON($content)
    {
        $pattern = '/{.*}/s';
        return preg_replace_callback($pattern, array($this, 'sanitizeJsonCallback'), $content);
    }

    /**
     * @param $matches
     * @return mixed
     */
    public function sanitizeJsonCallback($matches)
    {
        require_once GlobalConstant::$WP_SETUP;
        require_once GlobalConstant::$WP_TAXONOMY_PATH;
        return wp_slash($matches[0]);
    }
}

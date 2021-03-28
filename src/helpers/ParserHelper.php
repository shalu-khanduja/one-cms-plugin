<?php

namespace IDG2Migration\helpers;

use IDG2Migration\config\GlobalConstant;

class ParserHelper
{
    private DataFilter $dataFilter;

    public function __construct()
    {
        $this->dataFilter = new DataFilter();
    }

    /**
     * @param $xmlObject
     */
    public function parseXMLObject($xmlObject): array
    {
        $mappingArr = [];
        for ($i = 0; $i < $xmlObject->entities->entity->count(); ++$i) {
            $xmlObj = $xmlObject->entities->entity[$i];
            $mappingArr[$i]['source']['table_name'] = $this->dataFilter->getValueFromNameFromMappingXML(
                $xmlObj,
                'source_name'
            );
            $mappingArr[$i]['source']['has_meta'] = $this->dataFilter->getValueFromNameFromMappingXML(
                $xmlObj,
                'has_meta',
                'boolean'
            );
            $mappingArr[$i]['source']['taxonomy'] = $this->dataFilter->getValueFromNameFromMappingXML(
                $xmlObj,
                'taxonomy'
            );
            $mappingArr[$i]['destination']['table_name'] = $this->dataFilter->getValueFromNameFromMappingXML(
                $xmlObj,
                'destination_name'
            );
            $mappingArr[$i]['destination']['content_type'] = $this->dataFilter->getValueFromNameFromMappingXML(
                $xmlObj,
                'content_type'
            );
            // check if details are present for columns
            $k = 0;
            for ($j = 0; $j < $xmlObj->col->count(); ++$j) {
                if (!isset($xmlObj->col[$j]->attributes()['ignore'])) {
                    $mappingArr[$i]['source']['cols'][$k] = (string) $xmlObj->col[$j]->field;
                    $mappingArr[$i]['destination']['cols'][$k]['name'] = (string) $xmlObj->col[$j]->to[0];
                    $mappingArr[$i]['destination']['cols'][$k]['is_meta'] =
                        (bool) $xmlObj->col[$j]->to->attributes()['is_meta'];
                    $mappingArr[$i]['destination']['cols'][$k]['has_default'] =
                        (bool) $xmlObj->col[$j]->to->attributes()['has_default'];
                    $mappingArr[$i]['destination']['cols'][$k]['is_social_media'] =
                        (bool) $xmlObj->col[$j]->to->attributes()['is_social_media'];
                    $mappingArr[$i]['destination']['cols'][$k]['is_term_taxonomy'] =
                        (bool) $xmlObj->col[$j]->to->attributes()['is_term_taxonomy'];
                    $mappingArr[$i]['destination']['cols'][$k]['is_reference'] =
                        (bool) $xmlObj->col[$j]->to->attributes()['is_reference'];
                    $mappingArr[$i]['destination']['cols'][$k]['ref_taxonomy'] =
                        (string) $xmlObj->col[$j]->to->attributes()['ref_taxonomy'];
                    $mappingArr[$i]['destination']['cols'][$k]['ref_type'] =
                        (string) $xmlObj->col[$j]->to->attributes()['ref_type'];
                    $mappingArr[$i]['destination']['cols'][$k]['multi_title'] =
                        (int) $xmlObj->col[$j]->to->attributes()['multi_title'];
                    $mappingArr[$i]['destination']['cols'][$k]['region_info'] =
                        (int) $xmlObj->col[$j]->to->attributes()['region_info'];
                    $mappingArr[$i]['destination']['cols'][$k]['global_info'] =
                        (int) $xmlObj->col[$j]->to->attributes()['global_info'];
                    $mappingArr[$i]['destination']['cols'][$k]['path_key'] =
                        (string) $xmlObj->col[$j]->to->attributes()['path_key'];
                    $mappingArr[$i]['destination']['cols'][$k]['do_insert'] =
                        (string) $xmlObj->col[$j]->to->attributes()['do_insert'];
                    $mappingArr[$i]['destination']['cols'][$k]['is_callback'] =
                        (string) $xmlObj->col[$j]->to->attributes()['is_callback'];
                    $mappingArr[$i]['destination']['cols'][$k]['wp_term_relationships'] =
                        (int) $xmlObj->col[$j]->to->attributes()['wp_term_relationships'];
                    ++$k;
                }
            }
            // check if details are present for order clause
            if (count($xmlObj->order) > 0) {
                $z = 0;
                for ($j=0; $j < $xmlObj->order->count() ; $j++) {
                    $mappingArr[$i]['source']['order'][$z]['sort_by'] = $this->dataFilter->getValueFromNameFromMappingXML(
                        $xmlObj->order[$j],
                        'attribute'
                    );
                    $mappingArr[$i]['source']['order'][$z]['direction'] = $this->dataFilter->getValueFromNameFromMappingXML(
                        $xmlObj->order[$j],
                        'descending',
                        'boolean'
                    ) === 'true' ? 'desc' : 'asc';
                    ++$z;
                }
            }
            // check if details are present for groupby clause
            if (count($xmlObj->groupby) > 0) {
                $mappingArr[$i]['source']['groupby'] = $this->dataFilter->getValueFromNameFromMappingXML(
                    $xmlObj->groupby,
                    'attribute',
                    'string'
                );
            }
            // check if details are present for limit clause
            if (count($xmlObj->limit) > 0) {
                $mappingArr[$i]['source']['limit'] = $this->dataFilter->getValueFromNameFromMappingXML(
                    $xmlObj->limit,
                    'attribute',
                    'integer'
                );
            }
            // check if details are present for offset clause
            if (count($xmlObj->offset) > 0) {
                $mappingArr[$i]['source']['offset'] = $this->dataFilter->getValueFromNameFromMappingXML(
                    $xmlObj->offset,
                    'attribute',
                    'integer'
                );
            }
            // check if details are present for where clause
            if (count($xmlObj->filters) > 0) {
                $w = 0;
                $mappingArr[$i]['source']['conditions']['where_type'] =
                    $this->dataFilter->getValueFromNameFromMappingXML(
                        $xmlObj->filters,
                        'type'
                    );
                for ($x = 0; $x < $xmlObj->filters->condition->count(); ++$x) {
                    $mappingArr[$i]['source']['conditions']['where_conditions'][$w]['condition'] =
                    $this->dataFilter->getValueFromNameFromMappingXML(
                        $xmlObj->filters->condition[$x],
                        'attribute'
                    );
                    $mappingArr[$i]['source']['conditions']['where_conditions'][$w]['operator'] =
                    $this->dataFilter->getValueFromNameFromMappingXML(
                        $xmlObj->filters->condition[$x],
                        'operator'
                    );
                    $valueDataType = $this->dataFilter->getValueFromNameFromMappingXML(
                        $xmlObj->filters->condition[$x],
                        'value_type'
                    );
                    $mappingArr[$i]['source']['conditions']['where_conditions'][$w]['value'] =
                    $this->dataFilter->getValueFromNameFromMappingXML(
                        $xmlObj->filters->condition[$x],
                        'value',
                        $valueDataType
                    );
                    ++$w;
                }
            }

            // check if details are present for link/joins
            if (count($xmlObj->joins) > 0) {
                $mappingArr[$i]['source']['join_conditions'] = $this->joinsNodeHandler($xmlObj);
            }
        }

        return $mappingArr;
    }

    /**
     * @param $xmlObj
     * @return array
     */
    public function joinsNodeHandler($xmlObj): array
    {
        $joinArray = [];
        $sourceTable = $this->dataFilter->getValueFromNameFromMappingXML(
            $xmlObj,
            'source_name'
        );
        foreach ($xmlObj->joins->link_entity as $joinObj) {
            $joinType = $this->dataFilter->getValueFromNameFromMappingXML(
                $joinObj,
                'link_type'
            );
            $destinationTable = $this->dataFilter->getValueFromNameFromMappingXML(
                $joinObj,
                'table_name'
            );
            $destinationAlias = $this->dataFilter->getValueFromNameFromMappingXML(
                $joinObj,
                'alias'
            );
            $joinArray['alias'][$destinationTable] = $destinationAlias;
            $joinWhereCondition = $destinationAlias = $this->dataFilter->getValueFromNameFromMappingXML(
                $joinObj,
                'join_where'
            );
            if (strtolower($joinType) === 'inner') {
                $joinArray['condition'][] =
                    $this->linkInnerJoinNodeHandler($joinObj, $sourceTable, $joinWhereCondition);
            }

            if (strtolower($joinType) === 'left') {
                $joinArray['condition'][] = $this->linkLeftJoinNodeHandler($joinObj, $sourceTable, $joinWhereCondition);
            }
            $joinArray['join_where'][] = '';
            /*$joinArray['join_where'][] = $destinationAlias = $this->dataFilter->getValueFromNameFromMappingXML(
                $joinObj,
                'join_where'
            );*/
        }

        return $joinArray;
    }

    /**
     * @param $linkEntityObj
     * @param $sourceTable
     * @param $joinWhereCondition
     * @return string
     */
    public function linkInnerJoinNodeHandler($linkEntityObj, $sourceTable, $joinWhereCondition): string
    {
        $dontUseSourceAlias = $this->dataFilter->getValueFromNameFromMappingXML(
            $linkEntityObj,
            'dont_use_source_alias',
            'boolean'
        );

        $joinCondition = sprintf(
            '%s.%s = %s.%s',
            $dontUseSourceAlias ? '' : $sourceTable,
            $this->dataFilter->getValueFromNameFromMappingXML(
                $linkEntityObj,
                'to_column'
            ),
            $this->dataFilter->getValueFromNameFromMappingXML(
                $linkEntityObj,
                'table_name'
            ),
            $this->dataFilter->getValueFromNameFromMappingXML(
                $linkEntityObj,
                'from_column'
            )
        );

        return sprintf(
            'INNER JOIN %s ON %s %s ',
            $this->dataFilter->getValueFromNameFromMappingXML(
                $linkEntityObj,
                'table_name'
            ),
            ltrim($joinCondition, '.'),
            $joinWhereCondition
        );
    }

    public function parseXml($scriptParam)
    {
        $myXMLData = GlobalConstant::$MAPPING_DIR.$scriptParam.'mapping.xml.dist';
        $xml = simplexml_load_file($myXMLData) or die('Error: Cannot create object');

        return $xml;
    }

    /**
     * @param $linkEntityObj
     * @param $sourceTable
     * @param $joinWhereCondition
     * @return string
     */
    public function linkLeftJoinNodeHandler($linkEntityObj, $sourceTable, $joinWhereCondition): string
    {
        $dontUseSourceAlias = $this->dataFilter->getValueFromNameFromMappingXML(
            $linkEntityObj,
            'dont_use_source_alias',
            'boolean'
        );

        $joinCondition = sprintf(
            '%s.%s = %s.%s',
            $dontUseSourceAlias ? '' : $sourceTable,
            $this->dataFilter->getValueFromNameFromMappingXML(
                $linkEntityObj,
                'to_column'
            ),
            $this->dataFilter->getValueFromNameFromMappingXML(
                $linkEntityObj,
                'table_name'
            ),
            $this->dataFilter->getValueFromNameFromMappingXML(
                $linkEntityObj,
                'from_column'
            )
        );

        return sprintf(
            'LEFT JOIN %s ON %s %s ',
            $this->dataFilter->getValueFromNameFromMappingXML(
                $linkEntityObj,
                'table_name'
            ),
            ltrim($joinCondition, '.'),
            $joinWhereCondition
        );
    }
}

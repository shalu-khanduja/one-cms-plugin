<?php

namespace IDG2Migration\modules;

use IDG2Migration\db\SourceConnection;
use PDO;
use PDOException;

class FetcherModule
{
    private $sourceDB;

    /**
     * FetcherModule constructor.
     */
    public function __construct()
    {
        try {
            $this->sourceDB = SourceConnection::get()->connect();
        } catch (PDOException $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    public function fetchDataFromSource($item)
    {
        $sql = $this->generateQuery($item);
        //Assigned session variable for adding the SQL query into log in Handler file
        $_SESSION['executed_sql'] = $sql;
        return $this->sourceDB->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param  $item
     * @return string
     */
    public function generateQuery($item): string
    {
        $sql = 'SELECT ' . $this->generateColumns($item) . ' FROM ' . $item['source']['table_name'];
        $sql .= $this->generateJoinClause($item);
        $sql .= $this->generateJoinWhereClause($item);
        $sql .= $this->generateWhereClause($item);
        $sql .= $this->generateGroupByClause($item);
        $sql .= $this->generateOrderByClause($item);
        $sql .= $this->generateLimitClause($item);
        $sql .= $this->generateOffsetClause($item);

        return $sql;
    }

    /**
     * @param  $text
     * @return string
     */
    public function getOperatorByText($text): string
    {
        switch (strtolower($text)) {
            case 'gt':
                return '>';
            case 'lt':
                return '<';
            case 'eq':
                return '=';
            case 'neq':
                return '!=';
            case 'in':
                return 'in';
            case 'not in':
                return 'not in';
        }

        return '=';
    }

    /**
     * @param $type
     * @param $condition
     * @param $operator
     * @param $value
     *
     * @return string
     */
    public function whereClauseString($type, $condition, $operator, $value): string
    {
        switch (strtolower($type)) {
            case 'integer':
                return sprintf(
                    ' %s %s %d',
                    $condition,
                    $operator,
                    $value
                );
            case 'string':
                return sprintf(
                    ' %s %s %s',
                    $condition,
                    $operator,
                    $value
                );
        }

        return '';
    }

    /**
     * @param  $item
     * @return string
     */
    public function generateWhereClause($item): string
    {
        $sql = '';
        $conditionCount = count($item['source']['conditions']['where_conditions']);
        if ($conditionCount > 0) {
            for ($i = 0; $i <= $conditionCount; ++$i) {
                if ($i === 0) {
                    if (gettype($item['source']['conditions']['where_conditions'][$i]['value']) === 'integer') {
                        $sql .= ' WHERE' . $this->whereClauseString(
                                'integer',
                                $item['source']['conditions']['where_conditions'][$i]['condition'],
                                $this->getOperatorByText($item['source']['conditions']['where_conditions'][$i]['operator']),
                                $item['source']['conditions']['where_conditions'][$i]['value']
                            );
                    }
                    if (gettype($item['source']['conditions']['where_conditions'][$i]['value']) === 'string') {
                        $value = empty($item['source']['conditions']['where_conditions'][$i]['value'])
                        &&
                        $item['source']['conditions']['where_conditions'][$i]['operator'] == 'in'
                            ?
                            '(' . $item['in_operator_value'] . ')'
                            : $item['source']['conditions']['where_conditions'][$i]['value'];
                        $sql .= ' WHERE' . $this->whereClauseString(
                                'string',
                                $item['source']['conditions']['where_conditions'][$i]['condition'],
                                $this->getOperatorByText($item['source']['conditions']['where_conditions'][$i]['operator']),
                                $value
                            );
                    }
                } else {
                    if (gettype($item['source']['conditions']['where_conditions'][$i]['value']) === 'integer') {
                        $sql .= ' ' . strtoupper($item['source']['conditions']['where_type']) . '' . $this->whereClauseString(
                                'integer',
                                $item['source']['conditions']['where_conditions'][$i]['condition'],
                                $this->getOperatorByText($item['source']['conditions']['where_conditions'][$i]['operator']),
                                $item['source']['conditions']['where_conditions'][$i]['value']
                            );
                    }
                    if (gettype($item['source']['conditions']['where_conditions'][$i]['value']) === 'string') {
                        $value = empty($item['source']['conditions']['where_conditions'][$i]['value'])
                        &&
                        $item['source']['conditions']['where_conditions'][$i]['operator'] == 'in'
                            ?
                            '(' . $item['in_operator_value'] . ')'
                            : $item['source']['conditions']['where_conditions'][$i]['value'];
                        $sql .= ' ' . strtoupper($item['source']['conditions']['where_type']) . '' . $this->whereClauseString(
                                'string',
                                $item['source']['conditions']['where_conditions'][$i]['condition'],
                                $this->getOperatorByText($item['source']['conditions']['where_conditions'][$i]['operator']),
                                $value
                            );
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * @param  $item
     * @return string
     */
    public function generateLimitClause($item): string
    {
        $sql = '';
        if (isset($item['source']['limit'])) {
            $sql .= sprintf(' limit %d', $item['source']['limit']);
        }
        return $sql;
    }

    /**
     * @param  $item
     * @return string
     */
    public function generateOffsetClause($item): string
    {
        $sql = '';
        if (isset($item['source']['offset'])) {
            $sql .= sprintf(' offset %d', $item['source']['offset']);
        }
        return $sql;
    }

    /**
     * @param  $item
     * @return string
     */
    public function generateGroupByClause($item): string
    {
        $sql = '';
        if (isset($item['source']['groupby'])) {
            $sql .= sprintf(' group by %s', $item['source']['groupby']);
        }
        return $sql;
    }

    /**
     * @param  $item
     * @return string
     */
    public function generateOrderByClause($item): string
    {
        $sql = '';
        if (count($item['source']['order']) > 0) {
            $sql .= sprintf(
                ' order by '
            );
            foreach ($item['source']['order'] as $key => $value) {
                if (next($item['source']['order'])) {
                    $sql .= sprintf(
                        '%s %s, ',
                        $value['sort_by'],
                        $value['direction'],
                    );
                } else {
                    $sql .= sprintf(
                        '%s %s ',
                        $value['sort_by'],
                        $value['direction'],
                    );
                }
            }
        }
        return $sql;
    }

    /**
     * @param  $item
     * @return string
     */
    public function generateJoinClause($item): string
    {
        $sql = '';
        if (count($item['source']['join_conditions']['condition']) > 0) {
            for ($i = 0; $i <= count($item['source']['join_conditions']['condition']); ++$i) {
                $sql .= sprintf(
                    ' %s',
                    isset($item['source']['join_conditions']['condition'][$i])
                        ? $item['source']['join_conditions']['condition'][$i] : ''
                );
            }
        }
        return $sql;
    }

    /**
     * @param  $item
     * @return string
     */
    public function generateColumns($item): string
    {
        $pattern = "/(.*)\s(as|AS)\s(.*)$/m";
        $columnStr = '';

        foreach ($item['source']['cols'] as $col) {
            if (strstr($col, 'array_to_string')) {
                $columnStr .= (strlen($columnStr) === 0) ? $col : ', ' . $col;
            } elseif (preg_match($pattern, $col)) {
                $columnStr .= (strlen($columnStr) === 0) ? $col : ', ' . $col;
            } elseif (strstr($col, '.')) {
                $columnStr .= (strlen($columnStr) === 0) ?
                    $col . ' as ' . str_replace('.', '_', $col) : ', ' . $col . ' as ' . str_replace('.', '_', $col);
            }
        }
        return $columnStr;
    }

    /**
     * @param  $item
     * @return string
     */
    public function generateJoinWhereClause($item): string
    {
        $sql = '';
        if (isset($item['source']['join_conditions']['join_where']) && count($item['source']['join_conditions']['join_where']) > 0) {
            foreach ($item['source']['join_conditions']['join_where'] as $joinWhereCondition) {
                $sql .= !empty($joinWhereCondition) ? sprintf(' %s', $joinWhereCondition) : '';
            }
        }
        return $sql;
    }
}

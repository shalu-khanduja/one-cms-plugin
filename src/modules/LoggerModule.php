<?php

namespace IDG2Migration\modules;

use IDG2Migration\config\GlobalConstant;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use IDG2Migration\helpers\DataFilter;

class LoggerModule
{
    private Logger $logger;

    /**
     * LoggerModule constructor.
     *
     * @param string $logFileName
     */
    public function __construct($logFileName = '')
    {
        $this->logger = new Logger('migration_logger');
        $this->dataFilter = new DataFilter();
        $logFile = !empty($logFileName) ? trim($logFileName) : 'general';
        $this->logger->pushHandler(
            new StreamHandler(
                $this->dataFilter->getDirectory($logFile).$logFile.time().'.log',
                Logger::INFO
            )
        );
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function addInLog($data)
    {
        if (!empty($data['error'])) {
            $this->addLogForError($data);
        } else {
            $this->addStartEndDetails($data, 1);
            $this->addLogForEntity($data, 'entity');
            $this->addLogForResult($data, 'newId');
            $this->addLogForReference($data, 'reference');
            $this->addLogForEntity($data, 'meta');
            $this->addStartEndDetails($data, 2);
        }

        return true;
    }

    /**
     * @param $data
     * @param $type
     *
     * @return bool
     */
    public function addStartEndDetails($data, $type)
    {
        $this->logger->info('-----'.(($type == 1) ? 'Start' : 'End').' migration of '.$data['storyType'].
        ' {'.$data['name'].'}'.' One CMS ID {'.$data['oldId'].'} - New ID {'.$data['newId'].'}-----');

        return true;
    }

    /**
     * @param $data
     * @param $entity
     *
     * @return bool
     */
    public function addLogForEntity($data, $entity)
    {
        $entityLog = $data['oldId'].'-'.$data['newId'].' {'.$data['action'].'} '.$entity;
        foreach ($data[$entity] as $key => $value) {
            if (is_array($value)) {
                $entityLog .= ' -'.$key.'- {'.json_encode($value).'}';
            } else {
                $entityLog .= ' -'.$key.'- {'.$value.'}';
            }
        }

        $this->logger->info($entityLog);

        return true;
    }

    /**
     * @param $data
     * @param $field
     *
     * @return bool
     */
    public function addLogForResult($data, $field)
    {
        $this->logger->info($data['oldId'].'-'.$data['newId'].' Result {'.$data[$field].'}');

        return true;
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function addLogForError($data)
    {
        $this->logger->error(
            'Error migrating for -user_login -{'.$data['user']['cms_user_username'].'}-'.
            '-One CMS ID- {'.$data['user']['cms_user_id'].'}'.
            '-errorMessage- {'.$data['error'].'}'
        );

        return true;
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function addCountInLog($data)
    {
        $this->logger->info('-Total Records- {'.$data['total'].'} '.
            '-Total records migrated- {'.$data['migrated'].'}');

        return true;
    }
    /**
     * @param $data
     * @param $entity
     *
     * @return bool
     */
    public function addLogForReference($data, $entity)
    {
        $entityLog = $data['oldId'].'-'.$data['newId'].' Reference of ';
        foreach ($data[$entity] as $key => $value) {
            if (is_array($value['newId'])) {
                $entityLog .= ' -{'.$key.'}- from -{'.$value['oldId'].'}- to -{'.json_encode($value['newId']).'}-';
            } else {
                $entityLog .= ' -{'.$key.'}- from -{'.$value['oldId'].'}- to -{'.$value['newId'].'}-';
            }
        }

        $this->logger->info($entityLog);

        return true;
    }
}

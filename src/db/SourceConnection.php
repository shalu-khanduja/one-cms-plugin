<?php

namespace IDG2Migration\db;

use Exception;
use IDG2Migration\config\GlobalConstant;
use PDO;

class SourceConnection
{

    private static $conn;

    /**
     * Connect to the database and return an instance of \PDO object
     * @return PDO
     * @throws Exception
     */
    public function connect(): PDO
    {
        // read parameters in the ini configuration file
        $params = GlobalConstant::$PARAMS;
        if (count($params) <= 0) {
            throw new Exception("Error reading database configuration file");
        }
        // connect to the postgresql database
        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            $params['database'],
            $params['user'],
            $params['password']
        );

        $pdo = new PDO($conStr);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * return an instance of the Connection object
     */
    public static function get()
    {
        if (null === static::$conn) {
            static::$conn = new static();
        }

        return static::$conn;
    }

    protected function __construct()
    {
    }

    private function __clone()
    {
    }
}

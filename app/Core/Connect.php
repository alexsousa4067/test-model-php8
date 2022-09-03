<?php

namespace App\Core;

use PDO;
use PDOException;

class Connect
{
    private const OPTIONS = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ];

    private static PDO $instance;
    private static PDOException $fail;

    /**
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (empty(self::$instance)) {
            try {
                self::$instance = new PDO(
                    "mysql:host=" . getenv('CONF_DB_HOST') . ";dbname=" . getenv('CONF_DB_NAME') . ";port=" . getenv('CONF_DB_PORT'),
                    getenv('CONF_DB_USER'),
                    getenv("CONF_DB_PASS"),
                    self::OPTIONS
                );
            } catch (PDOException $exception) {
                self::$fail = $exception;
            }
        }
        return self::$instance;
    }

    /**
     * @return PDOException
     */
    public static function fail(): PDOException
    {
        return self::$fail;
    }


}

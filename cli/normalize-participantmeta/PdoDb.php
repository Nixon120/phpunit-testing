<?php

class PdoDb
{
    /**
     * @var \PDO|null
     */
    protected static $pdo = null;

    /**
     * @return PDO
     */
    public static function getConnection(): \PDO
    {
        if (self::$pdo === null) {
            self::init();
        }

        try {
            self::$pdo->query("SELECT 1");
        } catch (PDOException $e) {
            echo "Connection needs to be re-established, reinitializing..." . PHP_EOL;
            self::init();
        }
        return self::$pdo;
    }

    /**
     * @return PDO
     */
    protected static function init()
    {
        try {
            $host = getenv('MYSQL_HOST');
            $user = getenv('MYSQL_USERNAME');
            $pass = getenv('MYSQL_PASSWORD');
            $dbName = getenv('MYSQL_DATABASE');

            $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8";
            $opt = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            self::$pdo = new PDO($dsn, $user, $pass, $opt);
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}
<?php

class Database
{

    private static $instance = null;

    private $conn;



    private $host = 'localhost';

    private $db_name = 'tuncerda_emlak_imza';

    private $username = 'tuncerda_eimza';

    private $password = 'Td3492549/';

    private $charset = 'utf8mb4';



    private function __construct()
    {

        try {

            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";

            $options = [

                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES => false,

            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->conn->exec("SET NAMES 'utf8mb4'");
            $this->conn->exec("SET CHARACTER SET utf8mb4");
            $this->conn->exec("SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci'");

        } catch (PDOException $e) {

            die("Veritabanı bağlantısı kurulamadı.");

        }

    }



    public static function getInstance()
    {

        if (self::$instance === null) {

            self::$instance = new self();

        }

        return self::$instance;

    }



    public function getConnection()
    {

        return $this->conn;

    }



    private function __clone()
    {
    }

    public function __wakeup()
    {
    }

}



function getDB()
{

    return Database::getInstance()->getConnection();

}


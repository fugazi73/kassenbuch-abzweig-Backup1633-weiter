<?php
namespace App\Config;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new \mysqli(
            DB_HOST,
            DB_USER,
            DB_PASSWORD,
            DB_NAME
        );
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
} 
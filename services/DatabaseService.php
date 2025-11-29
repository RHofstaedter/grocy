<?php

namespace Grocy\Services;

use Grocy\Services\UsersService;
use LessQL\Database;
use Exception;

class DatabaseService
{
    private static ?\LessQL\Database $database = null;

    private static ?\PDO $pdo = null;

    private static ?\Grocy\Services\DatabaseService $databaseService = null;

    public function executeDbQuery(string $sql): \PDOStatement|false
    {
        $pdo = $this->getDbConnectionRaw();

        if ($this->executeDbStatement($sql)) {
            return $pdo->query($sql);
        }

        return false;
    }

    public function executeDbStatement(string $sql, ?array $params = null): bool
    {
        $pdo = $this->getDbConnectionRaw();

        if (GROCY_MODE === 'dev') {
            $logFilePath = GROCY_DATAPATH . '/sql.log';
            if (file_exists($logFilePath)) {
                file_put_contents($logFilePath, $sql . PHP_EOL, FILE_APPEND);
            }
        }

        if ($params == null) {
            if ($pdo->exec($sql) === false) {
                throw new Exception($pdo->errorInfo());
            }
        } else {
            $cmd = $pdo->prepare($sql);
            if ($cmd->execute($params) === false) {
                throw new Exception($pdo->errorInfo());
            }
        }

        return true;
    }

    public function getDbChangedTime(): string
    {
        return date('Y-m-d H:i:s', filemtime($this->getDbFilePath()));
    }

    public function getDbConnection(): \LessQL\Database
    {
        if (self::$database == null) {
            self::$database = new Database($this->getDbConnectionRaw());
        }

        if (GROCY_MODE === 'dev') {
            $logFilePath = GROCY_DATAPATH . '/sql.log';
            if (file_exists($logFilePath)) {
                self::$database->setQueryCallback(function (string $query, $params) use ($logFilePath): void {
                    file_put_contents($logFilePath, $query . ' #### ' . implode(';', $params) . PHP_EOL, FILE_APPEND);
                });
            }
        }

        return self::$database;
    }

    public function getDbConnectionRaw(): \PDO
    {
        if (self::$pdo == null) {
            $pdo = new \PDO('sqlite:' . $this->getDbFilePath());
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_EMPTY_STRING);

            $pdo->sqliteCreateFunction('regexp', function ($pattern, $value): int {
                mb_regex_encoding('UTF-8');
                return (mb_ereg($pattern, $value)) ? 1 : 0;
            });

            $pdo->sqliteCreateFunction('grocy_user_setting', function ($value) {
                $usersService = new UsersService();
                return $usersService->getUserSetting(GROCY_USER_ID, $value);
            });


            // Unfortunately not included by default
            // https://www.sqlite.org/lang_mathfunc.html#ceil
            $pdo->sqliteCreateFunction('ceil', fn($value): float => ceil($value));

            self::$pdo = $pdo;
        }

        return self::$pdo;
    }

    public function setDbChangedTime($dateTime): void
    {
        touch($this->getDbFilePath(), strtotime((string) $dateTime));
    }

    public static function getInstance(): \Grocy\Services\DatabaseService
    {
        if (self::$databaseService == null) {
            self::$databaseService = new self();
        }

        return self::$databaseService;
    }

    private function getDbFilePath(): string
    {
        if (GROCY_MODE === 'demo' || GROCY_MODE === 'prerelease') {
            $dbSuffix = GROCY_DEFAULT_LOCALE;
            if (defined('GROCY_DEMO_DB_SUFFIX')) {
                $dbSuffix = GROCY_DEMO_DB_SUFFIX;
            }

            return GROCY_DATAPATH . '/grocy_' . $dbSuffix . '.db';
        }

        return GROCY_DATAPATH . '/grocy.db';
    }
}

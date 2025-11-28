<?php

namespace Grocy\Services;

use Grocy\Services\UsersService;
use LessQL\Database;
use Exception;

class DatabaseService
{
    private static ?\LessQL\Database $DbConnection = null;

    private static ?\PDO $DbConnectionRaw = null;

    private static ?\Grocy\Services\DatabaseService $instance = null;

    public function executeDbQuery(string $sql)
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
        if (self::$DbConnection == null) {
            self::$DbConnection = new Database($this->getDbConnectionRaw());
        }

        if (GROCY_MODE === 'dev') {
            $logFilePath = GROCY_DATAPATH . '/sql.log';
            if (file_exists($logFilePath)) {
                self::$DbConnection->setQueryCallback(function (string $query, $params) use ($logFilePath): void {
                    file_put_contents($logFilePath, $query . ' #### ' . implode(';', $params) . PHP_EOL, FILE_APPEND);
                });
            }
        }

        return self::$DbConnection;
    }

    public function getDbConnectionRaw(): \PDO
    {
        if (self::$DbConnectionRaw == null) {
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

            self::$DbConnectionRaw = $pdo;
        }

        return self::$DbConnectionRaw;
    }

    public function setDbChangedTime($dateTime): void
    {
        touch($this->getDbFilePath(), strtotime((string) $dateTime));
    }

    public static function getInstance(): \Grocy\Services\DatabaseService
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
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

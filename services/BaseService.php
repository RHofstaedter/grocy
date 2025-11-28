<?php

namespace Grocy\Services;

class BaseService
{
    private static array $instances = [];

    public static function getInstance()
    {
        $className = static::class;
        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new $className();
        }

        return self::$instances[$className];
    }

    protected function getBatteriesService()
    {
        return BatteriesService::getInstance();
    }

    protected function getChoresService()
    {
        return ChoresService::getInstance();
    }

    protected function getDatabase(): \LessQL\Database
    {
        return $this->getDatabaseService()->getDbConnection();
    }

    protected function getDatabaseService(): \Grocy\Services\DatabaseService
    {
        return DatabaseService::getInstance();
    }

    protected function getLocalizationService()
    {
        if (!defined('GROCY_LOCALE')) {
            define('GROCY_LOCALE', GROCY_DEFAULT_LOCALE);
        }

        return LocalizationService::getInstance(GROCY_LOCALE);
    }

    protected function getStockService()
    {
        return StockService::getInstance();
    }

    protected function getTasksService()
    {
        return TasksService::getInstance();
    }

    protected function getUsersService()
    {
        return UsersService::getInstance();
    }

    protected function getPrintService()
    {
        return PrintService::getInstance();
    }

    protected function getFilesService()
    {
        return FilesService::getInstance();
    }

    protected function getApplicationService()
    {
        return ApplicationService::getInstance();
    }
}

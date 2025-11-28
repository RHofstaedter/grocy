<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Grocy\Services\ApiKeyService;
use Grocy\Services\ApplicationService;
use Grocy\Services\BatteriesService;
use Grocy\Services\CalendarService;
use Grocy\Services\ChoresService;
use Grocy\Services\DatabaseService;
use Grocy\Services\FilesService;
use Grocy\Services\LocalizationService;
use Grocy\Services\PrintService;
use Grocy\Services\RecipesService;
use Grocy\Services\SessionService;
use Grocy\Services\StockService;
use Grocy\Services\TasksService;
use Grocy\Services\UserfieldsService;
use Grocy\Services\UsersService;
use DI\Container;

class BaseController
{
    public function __construct(protected \DI\Container $AppContainer)
    {
        $this->View = $this->AppContainer->get('view');
    }

    protected $View;

    protected function getApiKeyService()
    {
        return ApiKeyService::getInstance();
    }

    protected function getApplicationservice()
    {
        return ApplicationService::getInstance();
    }

    protected function getBatteriesService()
    {
        return BatteriesService::getInstance();
    }

    protected function getCalendarService()
    {
        return CalendarService::getInstance();
    }

    protected function getChoresService()
    {
        return ChoresService::getInstance();
    }

    protected function getDatabase()
    {
        return $this->getDatabaseService()->getDbConnection();
    }

    protected function getDatabaseService(): \Grocy\Services\DatabaseService
    {
        return DatabaseService::getInstance();
    }

    protected function getFilesService()
    {
        return FilesService::getInstance();
    }

    protected function getLocalizationService()
    {
        if (!defined('GROCY_LOCALE')) {
            define('GROCY_LOCALE', GROCY_DEFAULT_LOCALE);
        }

        return LocalizationService::getInstance(GROCY_LOCALE);
    }

    protected function getRecipesService()
    {
        return RecipesService::getInstance();
    }

    protected function getSessionService()
    {
        return SessionService::getInstance();
    }

    protected function getStockService()
    {
        return StockService::getInstance();
    }

    protected function getPrintService()
    {
        return PrintService::getInstance();
    }

    protected function getTasksService()
    {
        return TasksService::getInstance();
    }

    protected function getUserfieldsService()
    {
        return UserfieldsService::getInstance();
    }

    protected function getUsersService()
    {
        return UsersService::getInstance();
    }

    protected function render($response, $viewName, $data = [])
    {
        $container = $this->AppContainer;

        $versionInfo = $this->getApplicationService()->getInstalledVersion();
        $this->View->set('version', $versionInfo->Version);

        $localizationService = $this->getLocalizationService();
        $this->View->set('__t', fn(string $text, ...$placeholderValues) => $localizationService->__t($text, $placeholderValues));
        $this->View->set('__n', fn($number, $singularForm, $pluralForm, $isQu = false) => $localizationService->__n($number, $singularForm, $pluralForm, $isQu));
        $this->View->set('LocalizationStrings', $localizationService->getPoAsJsonString());
        $this->View->set('LocalizationStringsQu', $localizationService->getPoAsJsonStringQu());

        // TODO: Better handle this generically based on the current language (header in .po file?)
        $dir = 'ltr';
        if (GROCY_LOCALE == 'he_IL') {
            $dir = 'rtl';
        }

        $this->View->set('dir', $dir);

        $this->View->set('U', fn($relativePath, $isResource = false) => $container->get('UrlManager')->ConstructUrl($relativePath, $isResource));

        $embedded = false;
        if (isset($_GET['embedded'])) {
            $embedded = true;
        }

        $this->View->set('embedded', $embedded);

        $constants = get_defined_constants();
        foreach (array_keys($constants) as $constant) {
            if (!str_starts_with($constant, 'GROCY_FEATURE_FLAG_')) {
                unset($constants[$constant]);
            }
        }

        $this->View->set('featureFlags', $constants);

        if (GROCY_AUTHENTICATED) {
            $this->View->set('permissions', User::permissionList());

            $decimalPlacesAmounts = $this->getUsersService()
                ->getUserSetting(GROCY_USER_ID, 'stock_decimal_places_amounts');
            $defaultMinAmount = $decimalPlacesAmounts <= 0 ? 1 : '0.' . str_repeat('0', $decimalPlacesAmounts - 1) . '1';

            $this->View->set('DEFAULT_MIN_AMOUNT', $defaultMinAmount);
        }

        $this->View->set('viewName', $viewName);

        return $this->View->render($response, $viewName, $data);
    }

    protected function renderPage($response, $viewName, $data = [])
    {
        $this->View->set('userentitiesForSidebar', $this->getDatabase()->userentities()->where('show_in_sidebar_menu = 1')->orderBy('name'));
        try {
            $usersService = $this->getUsersService();
            if (defined('GROCY_USER_ID')) {
                $this->View->set('userSettings', $usersService->getUserSettings(GROCY_USER_ID));
            } else {
                $this->View->set('userSettings', null);
            }
        } catch (\Exception) {
            // Happens when database is not initialised or migrated...
        }

        return $this->render($response, $viewName, $data);
    }
}

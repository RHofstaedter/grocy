<?php

namespace Grocy\Services;

use Gettext\Translation;
use Gettext\Translations;
use Gettext\Translator;

class LocalizationService
{
    public function __construct(string $culture)
    {
        $this->Culture = $culture;

        $this->loadLocalizations();
    }

    protected $Po;

    protected $PoQu;

    protected $Pot;

    protected $PotMain;

    protected $Translator;

    protected $TranslatorQu;

    protected $Culture;

    private static $instanceMap = [];

    public function checkAndAddMissingTranslationToPot($text)
    {
        if (GROCY_MODE === 'dev' && ($this->Pot->find('', $text) === false && empty($text) === false)) {
            $translation = new Translation('', $text);
            $this->PotMain[] = $translation;
            $this->PotMain->toPoFile(__DIR__ . '/../localization/strings.pot');
        }
    }

    public function getPluralCount(): int
    {
        if ($this->Po->getHeader(Translations::HEADER_PLURAL) !== null) {
            return intval($this->Po->getPluralForms()[0]);
        } else {
            return 2;
        }
    }

    public function getPluralDefinition()
    {
        if ($this->Po->getHeader(Translations::HEADER_PLURAL) !== null) {
            return $this->Po->getPluralForms()[1];
        } else {
            return '(n != 1)';
        }
    }

    public function getPoAsJsonString()
    {
        return $this->Po->toJsonString();
    }

    public function getPoAsJsonStringQu()
    {
        return $this->PoQu->toJsonString();
    }

    public function __n($number, $singularForm, $pluralForm, $isQu = false): string
    {
        $this->checkAndAddMissingTranslationToPot($singularForm);

        if (empty($pluralForm)) {
            $pluralForm = $singularForm;
        }

        if ($isQu) {
            return sprintf($this->TranslatorQu->ngettext($singularForm, $pluralForm, abs(floatval($number))), $number);
        } else {
            return sprintf($this->Translator->ngettext($singularForm, $pluralForm, abs(floatval($number))), $number);
        }
    }

    public function __t($text, ...$placeholderValues)
    {
        $this->checkAndAddMissingTranslationToPot($text);

        if (func_num_args() === 1) {
            return $this->Translator->gettext($text);
        } elseif (is_array(...$placeholderValues)) {
            return vsprintf($this->Translator->gettext($text), ...$placeholderValues);
        } else {
            return sprintf($this->Translator->gettext($text), array_shift($placeholderValues));
        }
    }

    public static function getInstance(string $culture)
    {
        if (!in_array($culture, self::$instanceMap)) {
            self::$instanceMap[$culture] = new self($culture);
        }

        return self::$instanceMap[$culture];
    }

    protected function getDatabaseService()
    {
        return DatabaseService::getInstance();
    }

    protected function getdatabase()
    {
        return $this->getDatabaseService()->getDbConnection();
    }

    private function loadLocalizations()
    {
        $culture = $this->Culture;

        if (GROCY_MODE === 'dev') {
            $this->PotMain = Translations::fromPoFile(__DIR__ . '/../localization/strings.pot');

            $this->Pot = Translations::fromPoFile(__DIR__ . '/../localization/chore_period_types.pot');
            $this->Pot = $this->Pot->mergeWith(Translations::fromPoFile(__DIR__ . '/../localization/chore_assignment_types.pot'));
            $this->Pot = $this->Pot->mergeWith(Translations::fromPoFile(__DIR__ . '/../localization/component_translations.pot'));
            $this->Pot = $this->Pot->mergeWith(Translations::fromPoFile(__DIR__ . '/../localization/stock_transaction_types.pot'));
            $this->Pot = $this->Pot->mergeWith(Translations::fromPoFile(__DIR__ . '/../localization/strings.pot'));
            $this->Pot = $this->Pot->mergeWith(Translations::fromPoFile(__DIR__ . '/../localization/userfield_types.pot'));
            $this->Pot = $this->Pot->mergeWith(Translations::fromPoFile(__DIR__ . '/../localization/permissions.pot'));
            $this->Pot = $this->Pot->mergeWith(Translations::fromPoFile(__DIR__ . '/../localization/locales.pot'));

            $this->Pot = $this->Pot->mergeWith(Translations::fromPoFile(__DIR__ . '/../localization/demo_data.pot'));
        }

        $this->Po = Translations::fromPoFile(__DIR__ . sprintf('/../localization/%s/strings.po', $culture));

        if (file_exists(__DIR__ . sprintf('/../localization/%s/chore_assignment_types.po', $culture))) {
            $this->Po = $this->Po->mergeWith(Translations::fromPoFile(__DIR__ . sprintf('/../localization/%s/chore_assignment_types.po', $culture)));
        }

        if (file_exists(__DIR__ . sprintf('/../localization/%s/component_translations.po', $culture))) {
            $this->Po = $this->Po->mergeWith(Translations::fromPoFile(__DIR__ . sprintf('/../localization/%s/component_translations.po', $culture)));
        }

        if (file_exists(__DIR__ . sprintf('/../localization/%s/stock_transaction_types.po', $culture))) {
            $this->Po = $this->Po->mergeWith(Translations::fromPoFile(__DIR__ . sprintf('/../localization/%s/stock_transaction_types.po', $culture)));
        }

        if (file_exists(__DIR__ . sprintf('/../localization/%s/chore_period_types.po', $culture))) {
            $this->Po = $this->Po->mergeWith(Translations::fromPoFile(__DIR__ . sprintf('/../localization/%s/chore_period_types.po', $culture)));
        }

        if (file_exists(__DIR__ . sprintf('/../localization/%s/userfield_types.po', $culture))) {
            $this->Po = $this->Po->mergeWith(Translations::fromPoFile(__DIR__ . sprintf('/../localization/%s/userfield_types.po', $culture)));
        }

        if (file_exists(__DIR__ . sprintf('/../localization/%s/permissions.po', $culture))) {
            $this->Po = $this->Po->mergeWith(Translations::fromPoFile(__DIR__ . sprintf('/../localization/%s/permissions.po', $culture)));
        }

        if (file_exists(__DIR__ . sprintf('/../localization/%s/locales.po', $culture))) {
            $this->Po = $this->Po->mergeWith(Translations::fromPoFile(__DIR__ . sprintf('/../localization/%s/locales.po', $culture)));
        }

        if (GROCY_MODE !== 'production' && file_exists(__DIR__ . sprintf('/../localization/%s/demo_data.po', $culture))) {
            $this->Po = $this->Po->mergeWith(Translations::fromPoFile(__DIR__ . sprintf('/../localization/%s/demo_data.po', $culture)));
        }

        $this->Translator = new Translator();
        $this->Translator->loadTranslations($this->Po);

        $this->PoQu = new Translations();

        $quantityUnits = null;
        try {
            $quantityUnits = $this->getDatabase()->quantity_units()->where('active = 1')->fetchAll();
        } catch (\Exception) {
            // Happens when database is not initialised or migrated...
        }

        if ($quantityUnits !== null) {
            $this->PoQu->setHeader(Translations::HEADER_PLURAL, $this->Po->getHeader(Translations::HEADER_PLURAL));

            foreach ($quantityUnits as $quantityUnit) {
                $translation = new Translation('', $quantityUnit['name']);
                $translation->setTranslation($quantityUnit['name']);
                $translation->setPlural($quantityUnit['name_plural']);
                $translation->setPluralTranslations(preg_split('/\r\n|\r|\n/', $quantityUnit['plural_forms'] ?? ''));

                $this->PoQu[] = $translation;
            }

            $this->TranslatorQu = new Translator();
            $this->TranslatorQu->loadTranslations($this->PoQu);
        };
    }
}

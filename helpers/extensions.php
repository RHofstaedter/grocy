<?php

global $GROCY_REQUIRED_FRONTEND_PACKAGES;

global $GROCY_DEFAULT_USER_SETTINGS;

$GROCY_REQUIRED_FRONTEND_PACKAGES = [];

$GROCY_DEFAULT_USER_SETTINGS = [];

function findObjectInArrayByPropertyValue($array, $propertyName, $propertyValue)
{
    foreach ($array as $object) {
        if ($object->{$propertyName} == $propertyValue) {
            return $object;
        }
    }

    return null;
}

/**
 * @return mixed[]
 */
function findAllObjectsInArrayByPropertyValue($array, $propertyName, $propertyValue, $operator = '=='): array
{
    $returnArray = [];
    foreach ($array as $object) {
        switch ($operator) {
            case '==':
                if ($object->{$propertyName} == $propertyValue) {
                    $returnArray[] = $object;
                }

                break;
            case '>':
                if ($object->{$propertyName} > $propertyValue) {
                    $returnArray[] = $object;
                }

                break;
            case '<':
                if ($object->{$propertyName} < $propertyValue) {
                    $returnArray[] = $object;
                }

                break;
        }
    }

    return $returnArray;
}

/**
 * @return mixed[]
 */
function findAllItemsInArrayByValue($array, $value, $operator = '=='): array
{
    $returnArray = [];
    foreach ($array as $item) {
        switch ($operator) {
            case '==':
                if ($item == $value) {
                    $returnArray[] = $item;
                }

                break;
            case '>':
                if ($item > $value) {
                    $returnArray[] = $item;
                }

                break;
            case '<':
                if ($item < $value) {
                    $returnArray[] = $item;
                }

                break;
        }
    }

    return $returnArray;
}

function sumArrayValue($array, $propertyName): float
{
    $sum = 0.0;
    foreach ($array as $object) {
        $sum += floatval($object->{$propertyName});
    }

    return $sum;
}

function getClassConstants($className, $prefix = null)
{
    $r = new ReflectionClass($className);
    $constants = $r->getConstants();

    if ($prefix === null) {
        return $constants;
    } else {
        $matchingKeys = preg_grep('!^' . $prefix . '!', array_keys($constants));
        return array_intersect_key($constants, array_flip($matchingKeys));
    }
}

function randomString($length, $allowedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
{
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $allowedChars[random_int(0, strlen($allowedChars) - 1)];
    }

    return $randomString;
}

function isAssociativeArray(array $array): bool
{
    $keys = array_keys($array);
    return array_keys($keys) !== $keys;
}

function isIsoDate($dateString): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $dateString);
    return $d && $d->format('Y-m-d') === $dateString;
}

function isIsoDateTime($dateTimeString): bool
{
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeString);
    return $d && $d->format('Y-m-d H:i:s') === $dateTimeString;
}

function boolToString(bool $bool): string
{
    return $bool ? 'true' : 'false';
}

function boolToInt(bool $bool): int
{
    return $bool ? 1 : 0;
}

function externalSettingValue(string $value): bool|string
{
    $tvalue = rtrim($value, "\r\n");
    $lvalue = strtolower($tvalue);

    if ($lvalue === 'true') {
        return true;
    } elseif ($lvalue === 'false') {
        return false;
    }

    return $tvalue;
}

function setting(string $name, $value): void
{
    if (!defined('GROCY_' . $name)) {
        // The content of a $name.txt file in /data/settingoverrides can overwrite the given setting (for embedded mode)
        $settingOverrideFile = GROCY_DATAPATH . '/settingoverrides/' . $name . '.txt';

        if (file_exists($settingOverrideFile)) {
            define('GROCY_' . $name, externalSettingValue(file_get_contents($settingOverrideFile)));
        } elseif (getenv('GROCY_' . $name) !== false) {
            // An environment variable with the same name and prefix GROCY_ overwrites the given setting
            define('GROCY_' . $name, externalSettingValue(getenv('GROCY_' . $name)));
        } else {
            define('GROCY_' . $name, $value);
        }
    }
}

function defaultUserSetting(string $name, $value): void
{
    global $GROCY_DEFAULT_USER_SETTINGS;

    if (!array_key_exists($name, $GROCY_DEFAULT_USER_SETTINGS)) {
        $GROCY_DEFAULT_USER_SETTINGS[$name] = $value;
    }
}

function getUserDisplayName(object $user): string
{
    return match (true) {
        empty($user->first_name) && !empty($user->last_name) => $user->last_name,
        empty($user->last_name) && !empty($user->first_name) => $user->first_name,
        !empty($user->first_name) && !empty($user->last_name) => $user->first_name . ' ' . $user->last_name,
        default => $user->username,
    };
}

function isValidFileName(string $fileName): bool
{
    return (bool) preg_match('=^[^/?*;:{}\\\\]+\.[^/?*;:{}\\\\]+$=', $fileName);
}

function isJsonString($text): bool
{
    json_decode((string) $text);
    return (json_last_error() === JSON_ERROR_NONE);
}

function require_frontend_packages(array $packages): void
{
    global $GROCY_REQUIRED_FRONTEND_PACKAGES;

    $GROCY_REQUIRED_FRONTEND_PACKAGES = array_unique(array_merge($GROCY_REQUIRED_FRONTEND_PACKAGES, $packages));
}

function emptyFolder($folderPath): void
{
    foreach (glob($folderPath . '/*') as $item) {
        if (is_dir($item)) {
            emptyFolder($item);
            rmdir($item);
        } else {
            unlink($item);
        }
    }
}

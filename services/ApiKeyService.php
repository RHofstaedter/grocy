<?php

namespace Grocy\Services;

class ApiKeyService extends BaseService
{
    public const API_KEY_TYPE_DEFAULT = 'default';

    public const API_KEY_TYPE_SPECIAL_PURPOSE_CALENDAR_ICAL = 'special-purpose-calendar-ical';

    public function createApiKey(string $keyType = self::API_KEY_TYPE_DEFAULT, ?string $description = null)
    {
        $newApiKey = $this->generateApiKey();

        $apiKeyRow = $this->getDatabase()->api_keys()->createRow([
            'api_key' => $newApiKey,
            'user_id' => GROCY_USER_ID,
            'expires' => '2999-12-31 23:59:59', // Default is that API keys never expire
            'key_type' => $keyType,
            'description' => $description
        ]);
        $apiKeyRow->save();

        return $newApiKey;
    }

    public function getApiKeyId($apiKey)
    {
        $apiKey = $this->getDatabase()->api_keys()->where('api_key', $apiKey)->fetch();
        return $apiKey->id;
    }

    // Returns any valid key for $keyType,
    // not allowed for key type "default"
    public function getOrCreateApiKey($keyType)
    {
        if ($keyType === self::API_KEY_TYPE_DEFAULT) {
            return null;
        } else {
            $apiKeyRow = $this->getDatabase()
                ->api_keys()
                ->where('key_type = :1 AND expires > :2', $keyType, date('Y-m-d H:i:s', time()))
                ->fetch();

            if ($apiKeyRow !== null) {
                return $apiKeyRow->api_key;
            } else {
                return $this->createApiKey($keyType);
            }
        }
    }

    public function getUserByApiKey($apiKey)
    {
        $apiKeyRow = $this->getDatabase()->api_keys()->where('api_key', $apiKey)->fetch();

        if ($apiKeyRow !== null) {
            return $this->getDatabase()->users($apiKeyRow->user_id);
        }

        return null;
    }

    public function isValidApiKey($apiKey, $keyType = self::API_KEY_TYPE_DEFAULT): bool
    {
        if ($apiKey === null || empty($apiKey)) {
            return false;
        } else {
            $apiKeyRow = $this->getDatabase()
                ->api_keys()
                ->where('api_key = :1 AND expires > :2 AND key_type = :3', $apiKey, date('Y-m-d H:i:s', time()), $keyType)
                ->fetch();

            if ($apiKeyRow !== null) {
                // This should not change the database file modification time as this is used
                // to determine if REALLY something has changed
                $dbModTime = $this->getDatabaseService()->getDbChangedTime();
                $apiKeyRow->update([
                    'last_used' => date('Y-m-d H:i:s', time())
                ]);
                $this->getDatabaseService()->setDbChangedTime($dbModTime);

                return true;
            } else {
                return false;
            }
        }
    }

    public function removeApiKey($apiKey): void
    {
        $this->getDatabase()->api_keys()->where('api_key', $apiKey)->delete();
    }

    private function generateApiKey(): string
    {
        return randomString(50);
    }
}

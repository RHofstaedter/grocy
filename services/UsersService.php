<?php

namespace Grocy\Services;

use LessQL\Result;
use Exception;

class UsersService extends BaseService
{
    private static $userSettingsCache = [];

    public function createUser(
        string $username,
        ?string $firstName,
        ?string $lastName,
        string $password,
        ?string $pictureFileName = null
    ) {
        $newUserRow = $this->getDatabase()->users()->createRow([
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'picture_file_name' => $pictureFileName
        ]);
        $newUserRow = $newUserRow->save();

        $permList = [];
        $permissions = $this->getDatabase()
            ->permission_hierarchy()
            ->where('name', GROCY_DEFAULT_PERMISSIONS)
            ->fetchAll();

        foreach ($permissions as $perm) {
            $permList[] = [
                'user_id' => $newUserRow->id,
                'permission_id' => $perm->id
            ];
        }

        $this->getDatabase()->user_permissions()->insert($permList);

        return $newUserRow;
    }

    public function deleteUser($userId)
    {
        $row = $this->getDatabase()->users($userId);
        $row->delete();
    }

    public function editUser(
        int $userId,
        string $username,
        string $firstName,
        string $lastName,
        ?string $password,
        ?string $pictureFileName = null
    ) {
        if (!$this->userExists($userId)) {
            throw new Exception('User does not exist');
        }

        $user = $this->getDatabase()->users($userId);

        if ($password === null || $password === '') {
            $user->update([
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'picture_file_name' => $pictureFileName
            ]);
        } else {
            $user->update([
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'picture_file_name' => $pictureFileName
            ]);
        }
    }

    public function getUserSetting($userId, $settingKey)
    {
        if (!array_key_exists($userId, self::$userSettingsCache)) {
            self::$userSettingsCache[$userId] = [];
        }

        if (array_key_exists($settingKey, self::$userSettingsCache[$userId])) {
            return self::$userSettingsCache[$userId][$settingKey];
        }

        $value = null;
        $settingRow = $this->getDatabase()->user_settings()->where('user_id = :1 AND key = :2', $userId, $settingKey)->fetch();
        if ($settingRow !== null) {
            $value = $settingRow->value;
        } else {
            // Use the configured default values for a missing setting, otherwise return NULL
            global $GROCY_DEFAULT_USER_SETTINGS;
            if (array_key_exists($settingKey, $GROCY_DEFAULT_USER_SETTINGS)) {
                $value = $GROCY_DEFAULT_USER_SETTINGS[$settingKey];
            }
        }

        self::$userSettingsCache[$userId][$settingKey] = $value;
        return $value;
    }

    public function getUserSettings($userId)
    {
        $settings = [];
        $settingRows = $this->getDatabase()->user_settings()->where('user_id = :1', $userId)->fetchAll();
        foreach ($settingRows as $settingRow) {
            $settings[$settingRow->key] = $settingRow->value;
        }

        // Use the configured default values for all missing settings
        global $GROCY_DEFAULT_USER_SETTINGS;
        return array_merge($GROCY_DEFAULT_USER_SETTINGS, $settings);
    }

    public function getUsersAsDto(): Result
    {
        return $this->getDatabase()->users_dto();
    }

    public function setUserSetting($userId, $settingKey, $settingValue)
    {
        if (!array_key_exists($userId, self::$userSettingsCache)) {
            self::$userSettingsCache[$userId] = [];
        }

        self::$userSettingsCache[$userId][$settingKey] = $settingValue;

        $settingRow = $this->getDatabase()->user_settings()->where('user_id = :1 AND key = :2', $userId, $settingKey)->fetch();
        if ($settingRow !== null) {
            $settingRow->update([
                'value' => $settingValue,
                'row_updated_timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            $settingRow = $this->getDatabase()->user_settings()->createRow([
                'user_id' => $userId,
                'key' => $settingKey,
                'value' => $settingValue
            ]);
            $settingRow->save();
        }
    }

    public function deleteUserSetting($userId, $settingKey)
    {
        if (!array_key_exists($userId, self::$userSettingsCache)) {
            self::$userSettingsCache[$userId] = [];
        }

        unset(self::$userSettingsCache[$userId][$settingKey]);

        $this->getDatabase()->user_settings()->where('user_id = :1 AND key = :2', $userId, $settingKey)->delete();
    }

    private function userExists($userId): bool
    {
        $userRow = $this->getDatabase()->users()->where('id = :1', $userId)->fetch();
        return $userRow !== null;
    }
}

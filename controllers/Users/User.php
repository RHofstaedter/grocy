<?php

namespace Grocy\Controllers\Users;

use Grocy\Services\DatabaseService;
use LessQL\Result;

class User
{
    public const PERMISSION_ADMIN = 'ADMIN';
    public const PERMISSION_BATTERIES = 'BATTERIES';
    public const PERMISSION_BATTERIES_TRACK_CHARGE_CYCLE = 'BATTERIES_TRACK_CHARGE_CYCLE';
    public const PERMISSION_BATTERIES_UNDO_CHARGE_CYCLE = 'BATTERIES_UNDO_CHARGE_CYCLE';
    public const PERMISSION_CALENDAR = 'CALENDAR';
    public const PERMISSION_CHORES = 'CHORES';
    public const PERMISSION_CHORE_TRACK_EXECUTION = 'CHORE_TRACK_EXECUTION';
    public const PERMISSION_CHORE_UNDO_EXECUTION = 'CHORE_UNDO_EXECUTION';
    public const PERMISSION_EQUIPMENT = 'EQUIPMENT';
    public const PERMISSION_MASTER_DATA_EDIT = 'MASTER_DATA_EDIT';
    public const PERMISSION_RECIPES = 'RECIPES';
    public const PERMISSION_RECIPES_MEALPLAN = 'RECIPES_MEALPLAN';
    public const PERMISSION_SHOPPINGLIST = 'SHOPPINGLIST';
    public const PERMISSION_SHOPPINGLIST_ITEMS_ADD = 'SHOPPINGLIST_ITEMS_ADD';
    public const PERMISSION_SHOPPINGLIST_ITEMS_DELETE = 'SHOPPINGLIST_ITEMS_DELETE';
    public const PERMISSION_STOCK = 'STOCK';
    public const PERMISSION_STOCK_CONSUME = 'STOCK_CONSUME';
    public const PERMISSION_STOCK_EDIT = 'STOCK_EDIT';
    public const PERMISSION_STOCK_INVENTORY = 'STOCK_INVENTORY';
    public const PERMISSION_STOCK_OPEN = 'STOCK_OPEN';
    public const PERMISSION_STOCK_PURCHASE = 'STOCK_PURCHASE';
    public const PERMISSION_STOCK_TRANSFER = 'STOCK_TRANSFER';
    public const PERMISSION_TASKS = 'TASKS';
    public const PERMISSION_TASKS_MARK_COMPLETED = 'TASKS_MARK_COMPLETED';
    public const PERMISSION_TASKS_UNDO_EXECUTION = 'TASKS_UNDO_EXECUTION';
    public const PERMISSION_USERS = 'USERS';
    public const PERMISSION_USERS_CREATE = 'USERS_CREATE';
    public const PERMISSION_USERS_EDIT = 'USERS_EDIT';
    public const PERMISSION_USERS_EDIT_SELF = 'USERS_EDIT_SELF';
    public const PERMISSION_USERS_READ = 'USERS_READ';

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getDbConnection();
    }

    protected $db;

    public static function permissionList()
    {
        $user = new self();
        return $user->getpermissionList();
    }

    public static function checkPermission($request, string $permission): void
    {
        $user = new self();
        if (!$user->hasPermission($permission)) {
            throw new PermissionMissingException($request, $permission);
        }
    }

    public function getpermissionList()
    {
        return $this->db->uihelper_user_permissions()->where('user_id', GROCY_USER_ID);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->getPermissions()->where('permission_name', $permission)->fetch() !== null;
    }

    public static function hasPermissions(string ...$permissions)
    {
        $user = new self();

        foreach ($permissions as $permission) {
            if (!$user->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    protected function getPermissions(): Result
    {
        return $this->db->user_permissions_resolved()->where('user_id', GROCY_USER_ID);
    }
}

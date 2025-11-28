<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class GenericEntityApiController extends BaseApiController
{
    public function addObject(Request $request, Response $response, array $args)
    {
        if ($args['entity'] == 'shopping_list' || $args['entity'] == 'shopping_lists') {
            User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_ADD);
        } elseif (in_array($args['entity'], ['recipes', 'recipes_pos', 'recipes_nestings'])) {
            User::checkPermission($request, User::PERMISSION_RECIPES);
        } elseif ($args['entity'] == 'meal_plan') {
            User::checkPermission($request, User::PERMISSION_RECIPES_MEALPLAN);
        } elseif ($args['entity'] == 'equipment') {
            User::checkPermission($request, User::PERMISSION_EQUIPMENT);
        } else {
            User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);
        }

        if ($this->isValidExposedEntity($args['entity']) && !$this->isEntityWithNoEdit($args['entity'])) {
            if ($this->isEntityWithEditRequiresAdmin($args['entity'])) {
                User::checkPermission($request, User::PERMISSION_ADMIN);
            }

            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            try {
                if ($requestBody === null) {
                    throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
                }

                $newRow = $this->getDatabase()->{$args['entity']}()->createRow($requestBody);
                $newRow->save();
                $newObjectId = $this->getDatabase()->lastInsertId();

                // TODO: This should be better done somehow in StockService
                if ($args['entity'] == 'products' && boolval($this->getUsersService()->getUserSetting(GROCY_USER_ID, 'shopping_list_auto_add_below_min_stock_amount'))) {
                    $this->getStockService()->addMissingProductsToShoppingList($this->getUsersService()->getUserSetting(GROCY_USER_ID, 'shopping_list_auto_add_below_min_stock_amount_list_id'));
                }

                return $this->apiResponse($response, [
                    'created_object_id' => $newObjectId
                ]);
            } catch (\Exception $ex) {
                return $this->genericErrorResponse($response, $ex->getMessage());
            }
        } else {
            return $this->genericErrorResponse($response, 'Entity does not exist or is not exposed');
        }
    }

    public function deleteObject(Request $request, Response $response, array $args)
    {
        if ($args['entity'] == 'shopping_list' || $args['entity'] == 'shopping_lists') {
            User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_DELETE);
        } elseif (in_array($args['entity'], ['recipes', 'recipes_pos', 'recipes_nestings'])) {
            User::checkPermission($request, User::PERMISSION_RECIPES);
        } elseif ($args['entity'] == 'meal_plan') {
            User::checkPermission($request, User::PERMISSION_RECIPES_MEALPLAN);
        } elseif ($args['entity'] == 'equipment') {
            User::checkPermission($request, User::PERMISSION_EQUIPMENT);
        } elseif ($args['entity'] == 'api_keys') {
            // Always allowed
        } else {
            User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);
        }

        if ($this->isValidExposedEntity($args['entity']) && !$this->isEntityWithNoDelete($args['entity'])) {
            if ($this->isEntityWithEditRequiresAdmin($args['entity'])) {
                User::checkPermission($request, User::PERMISSION_ADMIN);
            }

            $row = $this->getDatabase()->{$args['entity']}($args['objectId']);
            if ($row == null) {
                return $this->genericErrorResponse($response, 'Object not found', 400);
            }

            $row->delete();

            return $this->emptyApiResponse($response);
        } else {
            return $this->genericErrorResponse($response, 'Invalid entity');
        }
    }

    public function editObject(Request $request, Response $response, array $args)
    {
        if ($args['entity'] == 'shopping_list' || $args['entity'] == 'shopping_lists') {
            User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_ADD);
        } elseif (in_array($args['entity'], ['recipes', 'recipes_pos', 'recipes_nestings'])) {
            User::checkPermission($request, User::PERMISSION_RECIPES);
        } elseif ($args['entity'] == 'meal_plan') {
            User::checkPermission($request, User::PERMISSION_RECIPES_MEALPLAN);
        } elseif ($args['entity'] == 'equipment') {
            User::checkPermission($request, User::PERMISSION_EQUIPMENT);
        } else {
            User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);
        }

        if ($this->isValidExposedEntity($args['entity']) && !$this->isEntityWithNoEdit($args['entity'])) {
            if ($this->isEntityWithEditRequiresAdmin($args['entity'])) {
                User::checkPermission($request, User::PERMISSION_ADMIN);
            }

            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            try {
                if ($requestBody === null) {
                    throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
                }

                $row = $this->getDatabase()->{$args['entity']}($args['objectId']);
                if ($row == null) {
                    return $this->genericErrorResponse($response, 'Object not found', 400);
                }

                $row->update($requestBody);

                // TODO: This should be better done somehow in StockService
                if ($args['entity'] == 'products' && boolval($this->getUsersService()->getUserSetting(GROCY_USER_ID, 'shopping_list_auto_add_below_min_stock_amount'))) {
                    $this->getStockService()->addMissingProductsToShoppingList($this->getUsersService()->getUserSetting(GROCY_USER_ID, 'shopping_list_auto_add_below_min_stock_amount_list_id'));
                }

                return $this->emptyApiResponse($response);
            } catch (\Exception $ex) {
                return $this->genericErrorResponse($response, $ex->getMessage());
            }
        } else {
            return $this->genericErrorResponse($response, 'Entity does not exist or is not exposed');
        }
    }

    public function getObject(Request $request, Response $response, array $args)
    {
        if (!$this->isValidExposedEntity($args['entity']) || $this->isEntityWithNoListing($args['entity'])) {
            return $this->genericErrorResponse($response, 'Entity does not exist or is not exposed');
        }

        $object = $this->getDatabase()->{$args['entity']}($args['objectId']);
        if ($object == null) {
            return $this->genericErrorResponse($response, 'Object not found', 404);
        }

        // TODO: Handle this somehow more generically
        $referencingId = $args['objectId'];
        if ($args['entity'] == 'stock') {
            $referencingId = $object->stock_id;
        }

        $userfields = $this->getUserfieldsService()->getValues($args['entity'], $referencingId);
        if (count($userfields) === 0) {
            $userfields = null;
        }

        $object['userfields'] = $userfields;

        return $this->apiResponse($response, $object);
    }

    public function getObjects(Request $request, Response $response, array $args)
    {
        if (!$this->isValidExposedEntity($args['entity']) || $this->isEntityWithNoListing($args['entity'])) {
            return $this->genericErrorResponse($response, 'Entity does not exist or is not exposed');
        }

        $objects = $this->queryData($this->getDatabase()->{$args['entity']}(), $request->getQueryParams());

        $userfields = $this->getUserfieldsService()->getFields($args['entity']);
        if (count($userfields) > 0) {
            $allUserfieldValues = $this->getUserfieldsService()->getAllValues($args['entity']);

            foreach ($objects as $object) {
                $userfieldKeyValuePairs = null;
                foreach ($userfields as $userfield) {
                    // TODO: Handle this somehow more generically
                    $userfieldReference = 'id';
                    if ($args['entity'] == 'stock') {
                        $userfieldReference = 'stock_id';
                    }

                    $value = findObjectInArrayByPropertyValue(findAllObjectsInArrayByPropertyValue($allUserfieldValues, 'object_id', $object->{$userfieldReference}), 'name', $userfield->name);
                    $userfieldKeyValuePairs[$userfield->name] = $value?->value;
                }

                $object->userfields = $userfieldKeyValuePairs;
            }
        }

        return $this->apiResponse($response, $objects);
    }

    public function getUserfields(Request $request, Response $response, array $args)
    {
        try {
            return $this->apiResponse($response, $this->getUserfieldsService()->getValues($args['entity'], $args['objectId']));
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function setUserfields(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            if ($requestBody === null) {
                throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
            }

            $this->getUserfieldsService()->setValues($args['entity'], $args['objectId'], $requestBody);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    private function isEntityWithEditRequiresAdmin($entity): bool
    {
        return in_array($entity, $this->getOpenApiSpec()->components->schemas->ExposedEntityEditRequiresAdmin->enum);
    }

    private function isEntityWithNoListing($entity): bool
    {
        return in_array($entity, $this->getOpenApiSpec()->components->schemas->ExposedEntityNoListing->enum);
    }

    private function isEntityWithNoEdit($entity): bool
    {
        return in_array($entity, $this->getOpenApiSpec()->components->schemas->ExposedEntityNoEdit->enum);
    }

    private function isEntityWithNoDelete($entity): bool
    {
        return in_array($entity, $this->getOpenApiSpec()->components->schemas->ExposedEntityNoDelete->enum);
    }

    private function isValidExposedEntity($entity): bool
    {
        return in_array($entity, $this->getOpenApiSpec()->components->schemas->ExposedEntity->enum);
    }
}

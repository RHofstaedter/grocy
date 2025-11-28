<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Grocy\Helpers\WebhookRunner;
use Grocy\Helpers\Grocycode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class RecipesApiController extends BaseApiController
{
    public function addNotFulfilledProductsToShoppingList(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_ADD);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);
        $excludedProductIds = null;

        if ($requestBody !== null && array_key_exists('excludedProductIds', $requestBody)) {
            $excludedProductIds = $requestBody['excludedProductIds'];
        }

        $this->getRecipesService()->addNotFulfilledProductsToShoppingList($args['recipeId'], $excludedProductIds);
        return $this->emptyApiResponse($response);
    }

    public function consumeRecipe(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_CONSUME);

        try {
            $this->getRecipesService()->consumeRecipe($args['recipeId']);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function getRecipeFulfillment(Request $request, Response $response, array $args)
    {
        try {
            if (!isset($args['recipeId'])) {
                return $this->filteredApiResponse($response, $this->getRecipesService()->getRecipesResolved(), $request->getQueryParams());
            }

            $recipeResolved = findObjectInArrayByPropertyValue($this->getRecipesService()->getRecipesResolved(), 'recipe_id', $args['recipeId']);

            if (!$recipeResolved) {
                throw new Exception('Recipe does not exist');
            }

            return $this->apiResponse($response, $recipeResolved);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function copyRecipe(Request $request, Response $response, array $args)
    {
        try {
            return $this->apiResponse($response, [
                'created_object_id' => $this->getRecipesService()->copyRecipe($args['recipeId'])
            ]);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function recipePrintLabel(Request $request, Response $response, array $args)
    {
        try {
            $recipe = $this->getDatabase()->recipes()->where('id', $args['recipeId'])->fetch();

            $webhookData = array_merge([
                'recipe' => $recipe->name,
                'grocycode' => (string)(new Grocycode(Grocycode::RECIPE, $args['recipeId'])),
                'details' => $recipe
            ], GROCY_LABEL_PRINTER_PARAMS);

            if (GROCY_LABEL_PRINTER_RUN_SERVER) {
                (new WebhookRunner())->run(GROCY_LABEL_PRINTER_WEBHOOK, $webhookData, GROCY_LABEL_PRINTER_HOOK_JSON);
            }

            return $this->apiResponse($response, $webhookData);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }
}

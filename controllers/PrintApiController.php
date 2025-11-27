<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PrintApiController extends BaseApiController
{
    public function printShoppingListThermal(Request $request, Response $response, array $args)
    {
        try {
            User::checkPermission($request, User::PERMISSION_SHOPPINGLIST);

            $params = $request->getQueryParams();

            $listId = 1;
            if (isset($params['list'])) {
                $listId = $params['list'];
            }

            $printHeader = true;
            if (isset($params['printHeader'])) {
                $printHeader = ($params['printHeader'] === 'true');
            }
            $items = $this->getStockService()->getShoppinglistInPrintableStrings($listId);
            return $this->apiResponse($response, $this->getPrintService()->printShoppingList($printHeader, $items));
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }
}

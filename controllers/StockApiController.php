<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Grocy\Services\StockService;
use Grocy\Helpers\WebhookRunner;
use Grocy\Helpers\Grocycode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class StockApiController extends BaseApiController
{
    public function addMissingProductsToShoppingList(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_ADD);

        try {
            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            $listId = 1;

            if (array_key_exists('list_id', $requestBody) && !empty($requestBody['list_id']) && is_numeric($requestBody['list_id'])) {
                $listId = intval($requestBody['list_id']);
            }

            $this->getStockService()->addMissingProductsToShoppingList($listId);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function addOverdueProductsToShoppingList(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_ADD);

        try {
            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            $listId = 1;

            if (array_key_exists('list_id', $requestBody) && !empty($requestBody['list_id']) && is_numeric($requestBody['list_id'])) {
                $listId = intval($requestBody['list_id']);
            }

            $this->getStockService()->addOverdueProductsToShoppingList($listId);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function addExpiredProductsToShoppingList(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_ADD);

        try {
            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            $listId = 1;

            if (array_key_exists('list_id', $requestBody) && !empty($requestBody['list_id']) && is_numeric($requestBody['list_id'])) {
                $listId = intval($requestBody['list_id']);
            }

            $this->getStockService()->addExpiredProductsToShoppingList($listId);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function addProduct(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_PURCHASE);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            if ($requestBody === null) {
                throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
            }

            if (!array_key_exists('amount', $requestBody)) {
                throw new Exception('An amount is required');
            }

            $bestBeforeDate = null;
            if (array_key_exists('best_before_date', $requestBody) && isIsoDate($requestBody['best_before_date'])) {
                $bestBeforeDate = $requestBody['best_before_date'];
            }

            $purchasedDate = date('Y-m-d');
            if (array_key_exists('purchased_date', $requestBody) && isIsoDate($requestBody['purchased_date'])) {
                $purchasedDate = $requestBody['purchased_date'];
            }

            $price = null;
            if (array_key_exists('price', $requestBody) && is_numeric($requestBody['price'])) {
                $price = $requestBody['price'];
            }

            $locationId = null;
            if (array_key_exists('location_id', $requestBody) && is_numeric($requestBody['location_id'])) {
                $locationId = $requestBody['location_id'];
            }

            $shoppingLocationId = null;
            if (array_key_exists('shopping_location_id', $requestBody) && is_numeric($requestBody['shopping_location_id'])) {
                $shoppingLocationId = $requestBody['shopping_location_id'];
            }

            $transactionType = StockService::TRANSACTION_TYPE_PURCHASE;
            if (array_key_exists('transaction_type', $requestBody) && !empty($requestBody['transaction_type'])) {
                $transactionType = $requestBody['transaction_type'];
            }

            $stockLabelType = 0;
            if (array_key_exists('stock_label_type', $requestBody) && is_numeric($requestBody['stock_label_type'])) {
                $stockLabelType = intval($requestBody['stock_label_type']);
            }

            $note = null;
            if (array_key_exists('note', $requestBody)) {
                $note = $requestBody['note'];
            }

            $transactionId = $this->getStockService()->addProduct($args['productId'], $requestBody['amount'], $bestBeforeDate, $transactionType, $purchasedDate, $price, $locationId, $shoppingLocationId, $unusedTransactionId, $stockLabelType, false, $note);

            $args['transactionId'] = $transactionId;
            return $this->StockTransactions($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function addProductByBarcode(Request $request, Response $response, array $args)
    {
        try {
            $args['productId'] = $this->getStockService()->getProductIdFromBarcode($args['barcode']);
            return $this->addProduct($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function addProductToShoppingList(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_ADD);

        try {
            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            $listId = 1;
            $amount = 1;
            $quId = -1;
            $productId = null;
            $note = null;

            if (array_key_exists('list_id', $requestBody) && !empty($requestBody['list_id']) && is_numeric($requestBody['list_id'])) {
                $listId = intval($requestBody['list_id']);
            }

            if (array_key_exists('product_amount', $requestBody) && !empty($requestBody['product_amount']) && is_numeric($requestBody['product_amount'])) {
                $amount = intval($requestBody['product_amount']);
            }

            if (array_key_exists('product_id', $requestBody) && !empty($requestBody['product_id']) && is_numeric($requestBody['product_id'])) {
                $productId = intval($requestBody['product_id']);
            }

            if (array_key_exists('note', $requestBody) && !empty($requestBody['note'])) {
                $note = $requestBody['note'];
            }

            if (array_key_exists('qu_id', $requestBody) && !empty($requestBody['qu_id'])) {
                $quId = $requestBody['qu_id'];
            }

            if ($productId == null) {
                throw new Exception('No product id was supplied');
            }

            $this->getStockService()->addProductToShoppingList($productId, $amount, $quId, $note, $listId);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function clearShoppingList(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_DELETE);

        try {
            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            $listId = 1;
            if (array_key_exists('list_id', $requestBody) && !empty($requestBody['list_id']) && is_numeric($requestBody['list_id'])) {
                $listId = intval($requestBody['list_id']);
            }

            $doneOnly = false;
            if (array_key_exists('done_only', $requestBody) && filter_var($requestBody['done_only'], FILTER_VALIDATE_BOOLEAN)) {
                $doneOnly = boolval($requestBody['done_only']);
            }

            $this->getStockService()->clearShoppingList($listId, $doneOnly);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function consumeProduct(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_CONSUME);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            if ($requestBody === null) {
                throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
            }

            if (!array_key_exists('amount', $requestBody)) {
                throw new Exception('An amount is required');
            }

            $spoiled = false;
            if (array_key_exists('spoiled', $requestBody)) {
                $spoiled = $requestBody['spoiled'];
            }

            $transactionType = StockService::TRANSACTION_TYPE_CONSUME;
            if (array_key_exists('transaction_type', $requestBody) && !empty($requestBody['transactiontype'])) {
                $transactionType = $requestBody['transactiontype'];
            }

            $specificStockEntryId = 'default';
            if (array_key_exists('stock_entry_id', $requestBody) && !empty($requestBody['stock_entry_id'])) {
                $specificStockEntryId = $requestBody['stock_entry_id'];
            }

            $locationId = null;
            if (array_key_exists('location_id', $requestBody) && !empty($requestBody['location_id']) && is_numeric($requestBody['location_id'])) {
                $locationId = $requestBody['location_id'];
            }

            $recipeId = null;
            if (array_key_exists('recipe_id', $requestBody) && is_numeric($requestBody['recipe_id'])) {
                $recipeId = $requestBody['recipe_id'];
            }

            $consumeExact = false;
            if (array_key_exists('exact_amount', $requestBody)) {
                $consumeExact = $requestBody['exact_amount'];
            }

            $allowSubproductSubstitution = false;
            if (array_key_exists('allow_subproduct_substitution', $requestBody)) {
                $allowSubproductSubstitution = $requestBody['allow_subproduct_substitution'];
            }

            $transactionId = null;
            $transactionId = $this->getStockService()->consumeProduct($args['productId'], $requestBody['amount'], $spoiled, $transactionType, $specificStockEntryId, $recipeId, $locationId, $transactionId, $allowSubproductSubstitution, $consumeExact);
            $args['transactionId'] = $transactionId;
            return $this->StockTransactions($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function consumeProductByBarcode(Request $request, Response $response, array $args)
    {
        try {
            $args['productId'] = $this->getStockService()->getProductIdFromBarcode($args['barcode']);

            if (Grocycode::validate($args['barcode'])) {
                $grocycode = new Grocycode($args['barcode']);
                if ($grocycode->getExtraData()) {
                    $requestBody = $request->getParsedBody();
                    $requestBody['stock_entry_id'] = $grocycode->getExtraData()[0];
                    $request = $request->withParsedBody($requestBody);
                }
            }

            return $this->consumeProduct($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function currentStock(Request $request, Response $response, array $args)
    {
        return $this->apiResponse($response, $this->getStockService()->getCurrentStock());
    }

    public function CurrentVolatileStock(Request $request, Response $response, array $args)
    {
        $nextXDays = 5;

        if (isset($request->getQueryParams()['due_soon_days']) && !empty($request->getQueryParams()['due_soon_days']) && is_numeric($request->getQueryParams()['due_soon_days'])) {
            $nextXDays = $request->getQueryParams()['due_soon_days'];
        }

        $dueProducts = $this->getStockService()->getDueProducts($nextXDays, true);
        $overdueProducts = $this->getStockService()->getDueProducts(-1);
        $expiredProducts = $this->getStockService()->getExpiredProducts();
        $missingProducts = $this->getStockService()->getMissingProducts();
        return $this->apiResponse($response, [
            'due_products' => $dueProducts,
            'overdue_products' => $overdueProducts,
            'expired_products' => $expiredProducts,
            'missing_products' => $missingProducts
        ]);
    }

    public function editStockEntry(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_EDIT);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            if ($requestBody === null) {
                throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
            }

            if (!array_key_exists('amount', $requestBody)) {
                throw new Exception('An amount is required');
            }

            $bestBeforeDate = null;
            if (array_key_exists('best_before_date', $requestBody) && isIsoDate($requestBody['best_before_date'])) {
                $bestBeforeDate = $requestBody['best_before_date'];
            }

            $price = null;
            if (array_key_exists('price', $requestBody) && is_numeric($requestBody['price'])) {
                $price = $requestBody['price'];
            }

            $locationId = null;
            if (array_key_exists('location_id', $requestBody) && is_numeric($requestBody['location_id'])) {
                $locationId = $requestBody['location_id'];
            }

            $shoppingLocationId = null;
            if (array_key_exists('shopping_location_id', $requestBody) && is_numeric($requestBody['shopping_location_id'])) {
                $shoppingLocationId = $requestBody['shopping_location_id'];
            }

            $note = null;
            if (array_key_exists('note', $requestBody)) {
                $note = $requestBody['note'];
            }

            $transactionId = $this->getStockService()->editStockEntry($args['entryId'], $requestBody['amount'], $bestBeforeDate, $locationId, $shoppingLocationId, $price, $requestBody['open'], $requestBody['purchased_date'], $note);
            $args['transactionId'] = $transactionId;
            return $this->StockTransactions($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function externalBarcodeLookup(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);

        try {
            $addFoundProduct = false;
            if (isset($request->getQueryParams()['add']) && ($request->getQueryParams()['add'] === 'true' || $request->getQueryParams()['add'] === 1)) {
                $addFoundProduct = true;
            }

            return $this->apiResponse($response, $this->getStockService()->externalBarcodeLookup($args['barcode'], $addFoundProduct));
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function inventoryProduct(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_INVENTORY);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            if ($requestBody === null) {
                throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
            }

            if (!array_key_exists('new_amount', $requestBody)) {
                throw new Exception('An new amount is required');
            }

            $bestBeforeDate = null;
            if (array_key_exists('best_before_date', $requestBody) && isIsoDate($requestBody['best_before_date'])) {
                $bestBeforeDate = $requestBody['best_before_date'];
            }

            $purchasedDate = null;
            if (array_key_exists('purchased_date', $requestBody) && isIsoDate($requestBody['purchased_date'])) {
                $purchasedDate = $requestBody['purchased_date'];
            }

            $locationId = null;
            if (array_key_exists('location_id', $requestBody) && is_numeric($requestBody['location_id'])) {
                $locationId = $requestBody['location_id'];
            }

            $price = null;
            if (array_key_exists('price', $requestBody) && is_numeric($requestBody['price'])) {
                $price = $requestBody['price'];
            }

            $shoppingLocationId = null;
            if (array_key_exists('shopping_location_id', $requestBody) && is_numeric($requestBody['shopping_location_id'])) {
                $shoppingLocationId = $requestBody['shopping_location_id'];
            }

            $stockLabelType = 0;
            if (array_key_exists('stock_label_type', $requestBody) && is_numeric($requestBody['stock_label_type'])) {
                $stockLabelType = intval($requestBody['stock_label_type']);
            }

            $note = null;
            if (array_key_exists('note', $requestBody)) {
                $note = $requestBody['note'];
            }

            $transactionId = $this->getStockService()->inventoryProduct($args['productId'], $requestBody['new_amount'], $bestBeforeDate, $locationId, $price, $shoppingLocationId, $purchasedDate, $stockLabelType, $note);
            $args['transactionId'] = $transactionId;
            return $this->StockTransactions($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function inventoryProductByBarcode(Request $request, Response $response, array $args)
    {
        try {
            $args['productId'] = $this->getStockService()->getProductIdFromBarcode($args['barcode']);
            return $this->inventoryProduct($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function openProduct(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_OPEN);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            if ($requestBody === null) {
                throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
            }

            if (!array_key_exists('amount', $requestBody)) {
                throw new Exception('An amount is required');
            }

            $specificStockEntryId = 'default';
            if (array_key_exists('stock_entry_id', $requestBody) && !empty($requestBody['stock_entry_id'])) {
                $specificStockEntryId = $requestBody['stock_entry_id'];
            }

            $allowSubproductSubstitution = false;
            if (array_key_exists('allow_subproduct_substitution', $requestBody)) {
                $allowSubproductSubstitution = $requestBody['allow_subproduct_substitution'];
            }

            $transactionId = null;
            $transactionId = $this->getStockService()->openProduct($args['productId'], $requestBody['amount'], $specificStockEntryId, $transactionId, $allowSubproductSubstitution);
            $args['transactionId'] = $transactionId;
            return $this->StockTransactions($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function openProductByBarcode(Request $request, Response $response, array $args)
    {
        try {
            $args['productId'] = $this->getStockService()->getProductIdFromBarcode($args['barcode']);

            if (Grocycode::validate($args['barcode'])) {
                $grocycode = new Grocycode($args['barcode']);
                if ($grocycode->getExtraData()) {
                    $requestBody = $request->getParsedBody();
                    $requestBody['stock_entry_id'] = $grocycode->getExtraData()[0];
                    $request = $request->withParsedBody($requestBody);
                }
            }

            return $this->openProduct($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function ProductDetails(Request $request, Response $response, array $args)
    {
        try {
            return $this->apiResponse($response, $this->getStockService()->getProductDetails($args['productId']));
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function ProductDetailsByBarcode(Request $request, Response $response, array $args)
    {
        try {
            $productId = $this->getStockService()->getProductIdFromBarcode($args['barcode']);
            return $this->apiResponse($response, $this->getStockService()->getProductDetails($productId));
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function ProductPriceHistory(Request $request, Response $response, array $args)
    {
        try {
            return $this->apiResponse($response, $this->getStockService()->getProductPriceHistory($args['productId']));
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function ProductStockEntries(Request $request, Response $response, array $args)
    {
        $allowSubproductSubstitution = false;
        if (isset($request->getQueryParams()['include_sub_products']) && filter_var($request->getQueryParams()['include_sub_products'], FILTER_VALIDATE_BOOLEAN)) {
            $allowSubproductSubstitution = true;
        }

        return $this->filteredApiResponse($response, $this->getStockService()->getProductStockEntries($args['productId'], false, $allowSubproductSubstitution), $request->getQueryParams());
    }

    public function LocationStockEntries(Request $request, Response $response, array $args)
    {
        return $this->filteredApiResponse($response, $this->getStockService()->getLocationStockEntries($args['locationId']), $request->getQueryParams());
    }

    public function ProductStockLocations(Request $request, Response $response, array $args)
    {
        $allowSubproductSubstitution = false;
        if (isset($request->getQueryParams()['include_sub_products']) && filter_var($request->getQueryParams()['include_sub_products'], FILTER_VALIDATE_BOOLEAN)) {
            $allowSubproductSubstitution = true;
        }

        return $this->filteredApiResponse($response, $this->getStockService()->getProductStockLocations($args['productId'], $allowSubproductSubstitution), $request->getQueryParams());
    }

    public function ProductPrintLabel(Request $request, Response $response, array $args)
    {
        try {
            $productDetails = (object)$this->getStockService()->getProductDetails($args['productId']);

            $webhookData = array_merge([
                'product' => $productDetails->product->name,
                'grocycode' => (string)(new Grocycode(Grocycode::PRODUCT, $productDetails->product->id)),
                'details' => $productDetails,
            ], GROCY_LABEL_PRINTER_PARAMS);

            if (GROCY_LABEL_PRINTER_RUN_SERVER) {
                (new WebhookRunner())->run(GROCY_LABEL_PRINTER_WEBHOOK, $webhookData, GROCY_LABEL_PRINTER_HOOK_JSON);
            }

            return $this->apiResponse($response, $webhookData);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function StockEntryPrintLabel(Request $request, Response $response, array $args)
    {
        try {
            $stockEntry = $this->getDatabase()->stock()->where('id', $args['entryId'])->fetch();
            $productDetails = (object)$this->getStockService()->getProductDetails($stockEntry->product_id);

            $webhookData = array_merge([
                'product' => $productDetails->product->name,
                'grocycode' => (string)(new Grocycode(Grocycode::PRODUCT, $stockEntry->product_id, [$stockEntry->stock_id])),
                'details' => $productDetails,
                'stock_entry' => $stockEntry,
            ], GROCY_LABEL_PRINTER_PARAMS);

            if (GROCY_FEATURE_FLAG_STOCK_BEST_BEFORE_DATE_TRACKING) {
                $webhookData['due_date'] = $this->getLocalizationService()->__t('DD') . ': ' . $stockEntry->best_before_date;
            }

            if (GROCY_LABEL_PRINTER_RUN_SERVER) {
                (new WebhookRunner())->run(GROCY_LABEL_PRINTER_WEBHOOK, $webhookData, GROCY_LABEL_PRINTER_HOOK_JSON);
            }

            return $this->apiResponse($response, $webhookData);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function removeProductFromShoppingList(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_SHOPPINGLIST_ITEMS_DELETE);

        try {
            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            $listId = 1;
            $amount = 1;
            $productId = null;

            if (array_key_exists('list_id', $requestBody) && !empty($requestBody['list_id']) && is_numeric($requestBody['list_id'])) {
                $listId = intval($requestBody['list_id']);
            }

            if (array_key_exists('product_amount', $requestBody) && !empty($requestBody['product_amount']) && is_numeric($requestBody['product_amount'])) {
                $amount = intval($requestBody['product_amount']);
            }

            if (array_key_exists('product_id', $requestBody) && !empty($requestBody['product_id']) && is_numeric($requestBody['product_id'])) {
                $productId = intval($requestBody['product_id']);
            }

            if ($productId == null) {
                throw new Exception('No product id was supplied');
            }

            $this->getStockService()->removeProductFromShoppingList($productId, $amount, $listId);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function StockBooking(Request $request, Response $response, array $args)
    {
        try {
            $stockLogRow = $this->getDatabase()->stock_log($args['bookingId']);

            if ($stockLogRow === null) {
                throw new Exception('Stock booking does not exist');
            }

            return $this->apiResponse($response, $stockLogRow);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function StockEntry(Request $request, Response $response, array $args)
    {
        return $this->apiResponse($response, $this->getStockService()->getStockEntry($args['entryId']));
    }

    public function StockTransactions(Request $request, Response $response, array $args)
    {
        try {
            $transactionRows = $this->getDatabase()->stock_log()->where('transaction_id = :1', $args['transactionId'])->fetchAll();
            if (count($transactionRows) === 0) {
                throw new Exception('No transaction was found by the given transaction id');
            }

            return $this->apiResponse($response, $transactionRows);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function transferProduct(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_TRANSFER);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            if ($requestBody === null) {
                throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
            }

            if (!array_key_exists('amount', $requestBody)) {
                throw new Exception('An amount is required');
            }

            if (!array_key_exists('location_id_from', $requestBody)) {
                throw new Exception('A transfer from location is required');
            }

            if (!array_key_exists('location_id_to', $requestBody)) {
                throw new Exception('A transfer to location is required');
            }

            $specificStockEntryId = 'default';

            if (array_key_exists('stock_entry_id', $requestBody) && !empty($requestBody['stock_entry_id'])) {
                $specificStockEntryId = $requestBody['stock_entry_id'];
            }

            $transactionId = $this->getStockService()->transferProduct($args['productId'], $requestBody['amount'], $requestBody['location_id_from'], $requestBody['location_id_to'], $specificStockEntryId);
            $args['transactionId'] = $transactionId;
            return $this->StockTransactions($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function transferProductByBarcode(Request $request, Response $response, array $args)
    {
        try {
            $args['productId'] = $this->getStockService()->getProductIdFromBarcode($args['barcode']);

            if (Grocycode::validate($args['barcode'])) {
                $grocycode = new Grocycode($args['barcode']);
                if ($grocycode->getExtraData()) {
                    $requestBody = $request->getParsedBody();
                    $requestBody['stock_entry_id'] = $grocycode->getExtraData()[0];
                    $request = $request->withParsedBody($requestBody);
                }
            }

            return $this->transferProduct($request, $response, $args);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function undoBooking(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_EDIT);

        try {
            $this->apiResponse($response, $this->getStockService()->undoBooking($args['bookingId']));
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function undoTransaction(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_EDIT);

        try {
            $this->apiResponse($response, $this->getStockService()->undoTransaction($args['transactionId']));
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function mergeProducts(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_STOCK_EDIT);

        try {
            if (filter_var($args['productIdToKeep'], FILTER_VALIDATE_INT) === false || filter_var($args['productIdToRemove'], FILTER_VALIDATE_INT) === false) {
                throw new Exception('Provided {productIdToKeep} or {productIdToRemove} is not a valid integer');
            }

            $this->apiResponse($response, $this->getStockService()->mergeProducts($args['productIdToKeep'], $args['productIdToRemove']));
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }
}

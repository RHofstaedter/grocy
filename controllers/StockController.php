<?php

namespace Grocy\Controllers;

use Grocy\Helpers\Grocycode;
use Grocy\Services\RecipesService;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StockController extends BaseController
{
    use GrocycodeTrait;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        try {
            $externalBarcodeLookupPluginName = $this->getStockService()->getexternalBarcodeLookupPluginName();
        } catch (\Exception) {
            $externalBarcodeLookupPluginName = '';
        } finally {
            $this->View->set('externalBarcodeLookupPluginName', $externalBarcodeLookupPluginName);
        }
    }

    public function consume(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'consume', [
            'products' => $this->getDatabase()->products()->where('active = 1')->where('id IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)')->orderBy('name'),
            'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
            'recipes' => $this->getDatabase()->recipes()->where('type', RecipesService::RECIPE_TYPE_NORMAL)->orderBy('name', 'COLLATE NOCASE'),
            'locations' => $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved()
        ]);
    }

    public function inventory(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'inventory', [
            'products' => $this->getDatabase()->products()->where('active = 1 AND no_own_stock = 0')->orderBy('name', 'COLLATE NOCASE'),
            'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
            'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
            'userfields' => $this->getUserfieldsService()->getFields('stock')
        ]);
    }

    public function journal(Request $request, Response $response, array $args)
    {
        if (isset($request->getQueryParams()['months']) && filter_var($request->getQueryParams()['months'], FILTER_VALIDATE_INT) !== false) {
            $months = $request->getQueryParams()['months'];
            $where = sprintf("row_created_timestamp > DATE(DATE('now', 'localtime'), '-%s months')", $months);
        } else {
            // Default 6 months
            $where = "row_created_timestamp > DATE(DATE('now', 'localtime'), '-6 months')";
        }

        if (isset($request->getQueryParams()['product']) && filter_var($request->getQueryParams()['product'], FILTER_VALIDATE_INT) !== false) {
            $productId = $request->getQueryParams()['product'];
            $where .= ' AND product_id = ' . $productId;
        }

        $usersService = $this->getUsersService();

        return $this->renderPage($response, 'stockjournal', [
            'stockLog' => $this->getDatabase()->uihelper_stock_journal()->where($where)->orderBy('row_created_timestamp', 'DESC'),
            'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'locations' => $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE'),
            'users' => $usersService->getUsersAsDto(),
            'transactionTypes' => getClassConstants(\Grocy\Services\StockService::class, 'TRANSACTION_TYPE_'),
            'userfieldsStock' => $this->getUserfieldsService()->getFields('stock'),
            'userfieldValuesStock' => $this->getUserfieldsService()->getAllValues('stock')
        ]);
    }

    public function locationContentSheet(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'locationcontentsheet', [
            'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityunits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
            'locations' => $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE'),
            'currentStockLocationContent' => $this->getStockService()->getCurrentStockLocationContent(isset($request->getQueryParams()['include_out_of_stock']))
        ]);
    }

    public function locationEditForm(Request $request, Response $response, array $args)
    {
        if ($args['locationId'] == 'new') {
            return $this->renderPage($response, 'locationform', [
                'mode' => 'create',
                'userfields' => $this->getUserfieldsService()->getFields('locations')
            ]);
        }

        return $this->renderPage($response, 'locationform', [
            'location' => $this->getDatabase()->locations($args['locationId']),
            'mode' => 'edit',
            'userfields' => $this->getUserfieldsService()->getFields('locations')
        ]);
    }

    public function locationsList(Request $request, Response $response, array $args)
    {
        if (isset($request->getQueryParams()['include_disabled'])) {
            $locations = $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE');
        } else {
            $locations = $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
        }

        return $this->renderPage($response, 'locations', [
            'locations' => $locations,
            'userfields' => $this->getUserfieldsService()->getFields('locations'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('locations')
        ]);
    }

    public function overview(Request $request, Response $response, array $args)
    {
        $usersService = $this->getUsersService();
        $userSettings = $usersService->getUserSettings(GROCY_USER_ID);
        $nextXDays = $userSettings['stock_due_soon_days'];

        $where = 'is_in_stock_or_below_min_stock = 1';
        if (boolval($userSettings['stock_overview_show_all_out_of_stock_products'])) {
            $where = '1=1';
        }

        return $this->renderPage($response, 'stockoverview', [
            'currentStock' => $this->getDatabase()->uihelper_stock_current_overview()->where($where),
            'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'currentStockLocations' => $this->getStockService()->getCurrentStockLocations(),
            'nextXDays' => $nextXDays,
            'productGroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'userfields' => $this->getUserfieldsService()->getFields('products'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('products')
        ]);
    }

    public function productBarcodesEditForm(Request $request, Response $response, array $args)
    {
        $product = null;
        if (isset($request->getQueryParams()['product'])) {
            $product = $this->getDatabase()->products($request->getQueryParams()['product']);
        }

        if ($args['productBarcodeId'] == 'new') {
            return $this->renderPage($response, 'productbarcodeform', [
                'mode' => 'create',
                'barcodes' => $this->getDatabase()->product_barcodes()->orderBy('barcode'),
                'product' => $product,
                'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
                'quantityUnits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
                'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
                'userfields' => $this->getUserfieldsService()->getFields('product_barcodes')
            ]);
        }

        return $this->renderPage($response, 'productbarcodeform', [
            'mode' => 'edit',
            'barcode' => $this->getDatabase()->product_barcodes($args['productBarcodeId']),
            'product' => $product,
            'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
            'userfields' => $this->getUserfieldsService()->getFields('product_barcodes')
        ]);
    }

    public function productEditForm(Request $request, Response $response, array $args)
    {
        if ($args['productId'] == 'new') {
            return $this->renderPage($response, 'productform', [
                'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name'),
                'barcodes' => $this->getDatabase()->product_barcodes()->orderBy('barcode'),
                'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
                'quantityunitsStock' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
                'referencedQuantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
                'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
                'productgroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
                'userfields' => $this->getUserfieldsService()->getFields('products'),
                'products' => $this->getDatabase()->products()->where('parent_product_id IS NULL and active = 1')->orderBy('name', 'COLLATE NOCASE'),
                'isSubProductOfOthers' => false,
                'mode' => 'create'
            ]);
        }

        $product = $this->getDatabase()->products($args['productId']);
        return $this->renderPage($response, 'productform', [
            'product' => $product,
            'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'barcodes' => $this->getDatabase()->product_barcodes()->orderBy('barcode'),
            'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityunitsStock' => $this->getDatabase()->quantity_units()->where('id IN (SELECT to_qu_id FROM cache__quantity_unit_conversions_resolved WHERE product_id = :1) OR NOT EXISTS(SELECT 1 FROM stock_log WHERE product_id = :1)', $product->id)->orderBy('name', 'COLLATE NOCASE'),
            'referencedQuantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->where('id IN (SELECT to_qu_id FROM cache__quantity_unit_conversions_resolved WHERE product_id = :1)', $product->id)->orderBy('name', 'COLLATE NOCASE'),
            'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'productgroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'userfields' => $this->getUserfieldsService()->getFields('products'),
            'products' => $this->getDatabase()->products()->where('id != :1 AND parent_product_id IS NULL and active = 1', $product->id)->orderBy('name', 'COLLATE NOCASE'),
            'isSubProductOfOthers' => $this->getDatabase()->products()->where('parent_product_id = :1', $product->id)->count() !== 0,
            'mode' => 'edit',
            'quConversions' => $this->getDatabase()->quantity_unit_conversions()->where('product_id', $product->id),
            'productBarcodeUserfields' => $this->getUserfieldsService()->getFields('product_barcodes'),
            'productBarcodeUserfieldValues' => $this->getUserfieldsService()->getAllValues('product_barcodes')
        ]);
    }

    public function productGrocycodeImage(Request $request, Response $response, array $args)
    {
        $gc = new Grocycode(Grocycode::PRODUCT, $args['productId']);
        return $this->serveGrocycodeImage($request, $response, $gc);
    }

    public function productGroupEditForm(Request $request, Response $response, array $args)
    {
        if ($args['productGroupId'] == 'new') {
            return $this->renderPage($response, 'productgroupform', [
                'mode' => 'create',
                'userfields' => $this->getUserfieldsService()->getFields('product_groups')
            ]);
        }

        return $this->renderPage($response, 'productgroupform', [
            'group' => $this->getDatabase()->product_groups($args['productGroupId']),
            'mode' => 'edit',
            'userfields' => $this->getUserfieldsService()->getFields('product_groups')
        ]);
    }

    public function productGroupsList(Request $request, Response $response, array $args)
    {
        if (isset($request->getQueryParams()['include_disabled'])) {
            $productGroups = $this->getDatabase()->product_groups()->orderBy('name', 'COLLATE NOCASE');
        } else {
            $productGroups = $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
        }

        return $this->renderPage($response, 'productgroups', [
            'productGroups' => $productGroups,
            'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'userfields' => $this->getUserfieldsService()->getFields('product_groups'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('product_groups')
        ]);
    }

    public function productsList(Request $request, Response $response, array $args)
    {
        $products = $this->getDatabase()->products();
        if (!isset($request->getQueryParams()['include_disabled'])) {
            $products = $products->where('active = 1');
        }

        if (isset($request->getQueryParams()['only_in_stock'])) {
            $products = $products->where('id IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)');
        }

        if (isset($request->getQueryParams()['only_out_of_stock'])) {
            $products = $products->where('id NOT IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)');
        }

        $products = $products->orderBy('name', 'COLLATE NOCASE');

        return $this->renderPage($response, 'products', [
            'products' => $products,
            'locations' => $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE'),
            'quantityunits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
            'productGroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'shoppingLocations' => $this->getDatabase()->shopping_locations()->orderBy('name', 'COLLATE NOCASE'),
            'userfields' => $this->getUserfieldsService()->getFields('products'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('products')
        ]);
    }

    public function purchase(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'purchase', [
            'products' => $this->getDatabase()->products()->where('active = 1 AND no_own_stock = 0')->orderBy('name', 'COLLATE NOCASE'),
            'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
            'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
            'userfields' => $this->getUserfieldsService()->getFields('stock')
        ]);
    }

    public function quantityUnitConversionEditForm(Request $request, Response $response, array $args)
    {
        $product = null;
        if (isset($request->getQueryParams()['product'])) {
            $product = $this->getDatabase()->products($request->getQueryParams()['product']);
        }

        $defaultQuUnit = null;

        if (isset($request->getQueryParams()['qu-unit'])) {
            $defaultQuUnit = $this->getDatabase()->quantity_units($request->getQueryParams()['qu-unit']);
        }

        if ($args['quConversionId'] == 'new') {
            return $this->renderPage($response, 'quantityunitconversionform', [
                'mode' => 'create',
                'userfields' => $this->getUserfieldsService()->getFields('quantity_unit_conversions'),
                'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
                'product' => $product,
                'defaultQuUnit' => $defaultQuUnit
            ]);
        }

        return $this->renderPage($response, 'quantityunitconversionform', [
            'quConversion' => $this->getDatabase()->quantity_unit_conversions($args['quConversionId']),
            'mode' => 'edit',
            'userfields' => $this->getUserfieldsService()->getFields('quantity_unit_conversions'),
            'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'product' => $product,
            'defaultQuUnit' => $defaultQuUnit
        ]);
    }

    public function quantityUnitEditForm(Request $request, Response $response, array $args)
    {
        if ($args['quantityunitId'] == 'new') {
            return $this->renderPage($response, 'quantityunitform', [
                'mode' => 'create',
                'userfields' => $this->getUserfieldsService()->getFields('quantity_units'),
                'pluralCount' => $this->getLocalizationService()->getPluralCount(),
                'pluralRule' => $this->getLocalizationService()->getPluralDefinition()
            ]);
        }

        $quantityUnit = $this->getDatabase()->quantity_units($args['quantityunitId']);
        return $this->renderPage($response, 'quantityunitform', [
            'quantityUnit' => $quantityUnit,
            'mode' => 'edit',
            'userfields' => $this->getUserfieldsService()->getFields('quantity_units'),
            'pluralCount' => $this->getLocalizationService()->getPluralCount(),
            'pluralRule' => $this->getLocalizationService()->getPluralDefinition(),
            'defaultQuConversions' => $this->getDatabase()->quantity_unit_conversions()->where('from_qu_id = :1 AND product_id IS NULL', $quantityUnit->id),
            'quantityUnits' => $this->getDatabase()->quantity_units()
        ]);
    }

    public function quantityUnitPluralFormTesting(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'quantityunitpluraltesting', [
            'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE')
        ]);
    }

    public function quantityUnitsList(Request $request, Response $response, array $args)
    {
        if (isset($request->getQueryParams()['include_disabled'])) {
            $quantityUnits = $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE');
        } else {
            $quantityUnits = $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
        }

        return $this->renderPage($response, 'quantityunits', [
            'quantityunits' => $quantityUnits,
            'userfields' => $this->getUserfieldsService()->getFields('quantity_units'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('quantity_units')
        ]);
    }

    public function shoppingList(Request $request, Response $response, array $args)
    {
        $listId = 1;
        if (isset($request->getQueryParams()['list'])) {
            $listId = $request->getQueryParams()['list'];
        }

        return $this->renderPage($response, 'shoppinglist', [
            'listItems' => $this->getDatabase()->uihelper_shopping_list()->where('shopping_list_id = :1', $listId)->orderBy('product_name', 'COLLATE NOCASE'),
            'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityunits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
            'missingProducts' => $this->getStockService()->getMissingProducts(),
            'shoppingLists' => $this->getDatabase()->shopping_lists_view()->orderBy('name', 'COLLATE NOCASE'),
            'selectedShoppingListId' => $listId,
            'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
            'productUserfields' => $this->getUserfieldsService()->getFields('products'),
            'productUserfieldValues' => $this->getUserfieldsService()->getAllValues('products'),
            'productGroupUserfields' => $this->getUserfieldsService()->getFields('product_groups'),
            'productGroupUserfieldValues' => $this->getUserfieldsService()->getAllValues('product_groups'),
            'userfields' => $this->getUserfieldsService()->getFields('shopping_list'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('shopping_list')
        ]);
    }

    public function shoppingListEditForm(Request $request, Response $response, array $args)
    {
        if ($args['listId'] == 'new') {
            return $this->renderPage($response, 'shoppinglistform', [
                'mode' => 'create',
                'userfields' => $this->getUserfieldsService()->getFields('shopping_lists')
            ]);
        }

        return $this->renderPage($response, 'shoppinglistform', [
            'shoppingList' => $this->getDatabase()->shopping_lists($args['listId']),
            'mode' => 'edit',
            'userfields' => $this->getUserfieldsService()->getFields('shopping_lists')
        ]);
    }

    public function shoppingListItemEditForm(Request $request, Response $response, array $args)
    {
        if ($args['itemId'] == 'new') {
            return $this->renderPage($response, 'shoppinglistitemform', [
                'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
                'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
                'shoppingLists' => $this->getDatabase()->shopping_lists()->orderBy('name', 'COLLATE NOCASE'),
                'mode' => 'create',
                'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
                'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
                'userfields' => $this->getUserfieldsService()->getFields('shopping_list')
            ]);
        }

        return $this->renderPage($response, 'shoppinglistitemform', [
            'listItem' => $this->getDatabase()->shopping_list($args['itemId']),
            'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
            'shoppingLists' => $this->getDatabase()->shopping_lists()->orderBy('name', 'COLLATE NOCASE'),
            'mode' => 'edit',
            'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
            'userfields' => $this->getUserfieldsService()->getFields('shopping_list')
        ]);
    }

    public function shoppingListSettings(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'shoppinglistsettings', [
            'shoppingLists' => $this->getDatabase()->shopping_lists()->orderBy('name', 'COLLATE NOCASE')
        ]);
    }

    public function shoppingLocationEditForm(Request $request, Response $response, array $args)
    {
        if ($args['shoppingLocationId'] == 'new') {
            return $this->renderPage($response, 'shoppinglocationform', [
                'mode' => 'create',
                'userfields' => $this->getUserfieldsService()->getFields('shopping_locations')
            ]);
        }

        return $this->renderPage($response, 'shoppinglocationform', [
            'shoppingLocation' => $this->getDatabase()->shopping_locations($args['shoppingLocationId']),
            'mode' => 'edit',
            'userfields' => $this->getUserfieldsService()->getFields('shopping_locations')
        ]);
    }

    public function shoppingLocationsList(Request $request, Response $response, array $args)
    {
        if (isset($request->getQueryParams()['include_disabled'])) {
            $shoppingLocations = $this->getDatabase()->shopping_locations()->orderBy('name', 'COLLATE NOCASE');
        } else {
            $shoppingLocations = $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
        }

        return $this->renderPage($response, 'shoppinglocations', [
            'shoppinglocations' => $shoppingLocations,
            'userfields' => $this->getUserfieldsService()->getFields('shopping_locations'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('shopping_locations')
        ]);
    }

    public function stockEntryEditForm(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'stockentryform', [
            'stockEntry' => $this->getDatabase()->stock()->where('id', $args['entryId'])->fetch(),
            'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'userfields' => $this->getUserfieldsService()->getFields('stock')
        ]);
    }

    public function stockEntryGrocycodeImage(Request $request, Response $response, array $args)
    {
        $stockEntry = $this->getDatabase()->stock()->where('id', $args['entryId'])->fetch();
        $gc = new Grocycode(Grocycode::PRODUCT, $stockEntry->product_id, [$stockEntry->stock_id]);
        return $this->serveGrocycodeImage($request, $response, $gc);
    }

    public function stockEntryGrocycodeLabel(Request $request, Response $response, array $args)
    {
        $stockEntry = $this->getDatabase()->stock()->where('id', $args['entryId'])->fetch();
        return $this->renderPage($response, 'stockentrylabel', [
            'stockEntry' => $stockEntry,
            'product' => $this->getDatabase()->products($stockEntry->product_id),
        ]);
    }

    public function stockSettings(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'stocksettings', [
            'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'productGroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE')
        ]);
    }

    public function stockentries(Request $request, Response $response, array $args)
    {
        $usersService = $this->getUsersService();
        $nextXDays = $usersService->getUserSettings(GROCY_USER_ID)['stock_due_soon_days'];

        return $this->renderPage($response, 'stockentries', [
            'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'stockEntries' => $this->getDatabase()->uihelper_stock_entries()->orderBy('product_id'),
            'currentStockLocations' => $this->getStockService()->getCurrentStockLocations(),
            'nextXDays' => $nextXDays,
            'userfieldsProducts' => $this->getUserfieldsService()->getFields('products'),
            'userfieldValuesProducts' => $this->getUserfieldsService()->getAllValues('products'),
            'userfieldsStock' => $this->getUserfieldsService()->getFields('stock'),
            'userfieldValuesStock' => $this->getUserfieldsService()->getAllValues('stock')
        ]);
    }

    public function transfer(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'transfer', [
            'products' => $this->getDatabase()->products()->where('active = 1')->where('no_own_stock = 0 AND id IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)')->orderBy('name', 'COLLATE NOCASE'),
            'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
            'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved()
        ]);
    }

    public function journalSummary(Request $request, Response $response, array $args)
    {
        $entries = $this->getDatabase()->uihelper_stock_journal_summary();
        if (isset($request->getQueryParams()['product_id'])) {
            $entries = $entries->where('product_id', $request->getQueryParams()['product_id']);
        }

        if (isset($request->getQueryParams()['user_id'])) {
            $entries = $entries->where('user_id', $request->getQueryParams()['user_id']);
        }

        if (isset($request->getQueryParams()['transaction_type'])) {
            $entries = $entries->where('transaction_type', $request->getQueryParams()['transaction_type']);
        }

        $usersService = $this->getUsersService();
        return $this->renderPage($response, 'stockjournalsummary', [
            'entries' => $entries,
            'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'users' => $usersService->getUsersAsDto(),
            'transactionTypes' => getClassConstants(\Grocy\Services\StockService::class, 'TRANSACTION_TYPE_')
        ]);
    }

    public function quantityUnitConversionsResolved(Request $request, Response $response, array $args)
    {
        $product = null;
        if (isset($request->getQueryParams()['product'])) {
            $product = $this->getDatabase()->products($request->getQueryParams()['product']);
            $quantityUnitConversionsResolved = $this->getDatabase()->cache__quantity_unit_conversions_resolved()->where('product_id', $product->id);
        } else {
            $quantityUnitConversionsResolved = $this->getDatabase()->cache__quantity_unit_conversions_resolved()->where('product_id IS NULL');
        }

        return $this->renderPage($response, 'quantityunitconversionsresolved', [
            'product' => $product,
            'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'quantityUnitConversionsResolved' => $quantityUnitConversionsResolved
        ]);
    }
}

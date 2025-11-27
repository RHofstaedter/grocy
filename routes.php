<?php

use Grocy\Middleware\JsonMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

$app->group('', function (RouteCollectorProxy $group) {
	// System routes
	$group->get('/', '\Grocy\Controllers\SystemController:root')->setName('root');
	$group->get('/about', '\Grocy\Controllers\SystemController:about');
	$group->get('/manifest', '\Grocy\Controllers\SystemController:manifest');
	$group->get('/barcodescannertesting', '\Grocy\Controllers\SystemController:barcodeScannerTesting');

	// Login routes
	$group->get('/login', '\Grocy\Controllers\LoginController:loginPage')->setName('login');
	$group->post('/login', '\Grocy\Controllers\LoginController:processLogin')->setName('login');
	$group->get('/logout', '\Grocy\Controllers\LoginController:logout');

	// Generic entity interaction
	$group->get('/userfields', '\Grocy\Controllers\GenericEntityController:userfieldsList');
	$group->get('/userfield/{userfieldId}', '\Grocy\Controllers\GenericEntityController:userfieldEditForm');
	$group->get('/userentities', '\Grocy\Controllers\GenericEntityController:userentitiesList');
	$group->get('/userentity/{userentityId}', '\Grocy\Controllers\GenericEntityController:userentityEditForm');
	$group->get('/userobjects/{userentityName}', '\Grocy\Controllers\GenericEntityController:userobjectsList');
	$group->get('/userobject/{userentityName}/{userobjectId}', '\Grocy\Controllers\GenericEntityController:userobjectEditForm');

	// User routes
	$group->get('/users', '\Grocy\Controllers\UsersController:usersList');
	$group->get('/user/{userId}', '\Grocy\Controllers\UsersController:userEditForm');
	$group->get('/user/{userId}/permissions', '\Grocy\Controllers\UsersController:permissionList');
	$group->get('/usersettings', '\Grocy\Controllers\UsersController:userSettings');

	$group->get('/files/{group}/{fileName}', '\Grocy\Controllers\FilesApiController:showFile');

	// Stock master data routes
	$group->get('/products', '\Grocy\Controllers\StockController:productsList');
	$group->get('/product/{productId}', '\Grocy\Controllers\StockController:productEditForm');
	$group->get('/quantityunits', '\Grocy\Controllers\StockController:quantityUnitsList');
	$group->get('/quantityunit/{quantityunitId}', '\Grocy\Controllers\StockController:quantityUnitEditForm');
	$group->get('/quantityunitconversion/{quConversionId}', '\Grocy\Controllers\StockController:quantityUnitConversionEditForm');
	$group->get('/productgroups', '\Grocy\Controllers\StockController:productGroupsList');
	$group->get('/productgroup/{productGroupId}', '\Grocy\Controllers\StockController:productGroupEditForm');
	$group->get('/product/{productId}/grocycode', '\Grocy\Controllers\StockController:productGrocycodeImage');

	// Stock handling routes
	$group->get('/stockoverview', '\Grocy\Controllers\StockController:overview');
	$group->get('/stockentries', '\Grocy\Controllers\StockController:stockentries');
	$group->get('/purchase', '\Grocy\Controllers\StockController:purchase');
	$group->get('/consume', '\Grocy\Controllers\StockController:consume');
	$group->get('/transfer', '\Grocy\Controllers\StockController:transfer');
	$group->get('/inventory', '\Grocy\Controllers\StockController:inventory');
	$group->get('/stockentry/{entryId}', '\Grocy\Controllers\StockController:stockEntryEditForm');
	$group->get('/stocksettings', '\Grocy\Controllers\StockController:stockSettings');
	$group->get('/locations', '\Grocy\Controllers\StockController:locationsList');
	$group->get('/location/{locationId}', '\Grocy\Controllers\StockController:locationEditForm');
	$group->get('/stockjournal', '\Grocy\Controllers\StockController:journal');
	$group->get('/locationcontentsheet', '\Grocy\Controllers\StockController:locationContentSheet');
	$group->get('/quantityunitpluraltesting', '\Grocy\Controllers\StockController:quantityUnitPluralFormTesting');
	$group->get('/stockjournal/summary', '\Grocy\Controllers\StockController:journalSummary');
	$group->get('/productbarcodes/{productBarcodeId}', '\Grocy\Controllers\StockController:productBarcodesEditForm');
	$group->get('/stockentry/{entryId}/grocycode', '\Grocy\Controllers\StockController:stockEntryGrocycodeImage');
	$group->get('/stockentry/{entryId}/label', '\Grocy\Controllers\StockController:stockEntryGrocycodeLabel');
	$group->get('/quantityunitconversionsresolved', '\Grocy\Controllers\StockController:quantityUnitConversionsResolved');
	$group->get('/stockreports/spendings', '\Grocy\Controllers\StockReportsController:spendings');

	// Stock price tracking
	$group->get('/shoppinglocations', '\Grocy\Controllers\StockController:shoppingLocationsList');
	$group->get('/shoppinglocation/{shoppingLocationId}', '\Grocy\Controllers\StockController:shoppingLocationEditForm');

	// Shopping list routes
	$group->get('/shoppinglist', '\Grocy\Controllers\StockController:shoppingList');
	$group->get('/shoppinglistitem/{itemId}', '\Grocy\Controllers\StockController:shoppingListItemEditForm');
	$group->get('/shoppinglist/{listId}', '\Grocy\Controllers\StockController:shoppingListEditForm');
	$group->get('/shoppinglistsettings', '\Grocy\Controllers\StockController:shoppingListSettings');

	// Recipe routes
	$group->get('/recipes', '\Grocy\Controllers\RecipesController:overview');
	$group->get('/recipe/{recipeId}', '\Grocy\Controllers\RecipesController:recipeEditForm');
	$group->get('/recipe/{recipeId}/pos/{recipePosId}', '\Grocy\Controllers\RecipesController:recipePosEditForm');
	$group->get('/recipessettings', '\Grocy\Controllers\RecipesController:recipesSettings');
	$group->get('/recipe/{recipeId}/grocycode', '\Grocy\Controllers\RecipesController:recipeGrocycodeImage');

	// Meal plan routes
	$group->get('/mealplan', '\Grocy\Controllers\RecipesController:mealPlan');
	$group->get('/mealplansections', '\Grocy\Controllers\RecipesController:mealPlanSectionsList');
	$group->get('/mealplansection/{sectionId}', '\Grocy\Controllers\RecipesController:mealPlanSectionEditForm');

	// Chore routes
	$group->get('/choresoverview', '\Grocy\Controllers\ChoresController:overview');
	$group->get('/choretracking', '\Grocy\Controllers\ChoresController:trackChoreExecution');
	$group->get('/choresjournal', '\Grocy\Controllers\ChoresController:journal');
	$group->get('/chores', '\Grocy\Controllers\ChoresController:choresList');
	$group->get('/chore/{choreId}', '\Grocy\Controllers\ChoresController:choreEditForm');
	$group->get('/choressettings', '\Grocy\Controllers\ChoresController:choresSettings');
	$group->get('/chore/{choreId}/grocycode', '\Grocy\Controllers\ChoresController:choreGrocycodeImage');

	// Battery routes
	$group->get('/batteriesoverview', '\Grocy\Controllers\BatteriesController:overview');
	$group->get('/batterytracking', '\Grocy\Controllers\BatteriesController:trackChargeCycle');
	$group->get('/batteriesjournal', '\Grocy\Controllers\BatteriesController:journal');
	$group->get('/batteries', '\Grocy\Controllers\BatteriesController:batteriesList');
	$group->get('/battery/{batteryId}', '\Grocy\Controllers\BatteriesController:batteryEditForm');
	$group->get('/batteriessettings', '\Grocy\Controllers\BatteriesController:batteriesSettings');
	$group->get('/battery/{batteryId}/grocycode', '\Grocy\Controllers\BatteriesController:batteryGrocycodeImage');

	// Task routes
	$group->get('/tasks', '\Grocy\Controllers\TasksController:overview');
	$group->get('/task/{taskId}', '\Grocy\Controllers\TasksController:taskEditForm');
	$group->get('/taskcategories', '\Grocy\Controllers\TasksController:taskCategoriesList');
	$group->get('/taskcategory/{categoryId}', '\Grocy\Controllers\TasksController:taskCategoryEditForm');
	$group->get('/taskssettings', '\Grocy\Controllers\TasksController:tasksSettings');

	// Equipment routes
	$group->get('/equipment', '\Grocy\Controllers\EquipmentController:overview');
	$group->get('/equipment/{equipmentId}', '\Grocy\Controllers\EquipmentController:editForm');

	// Calendar routes
	$group->get('/calendar', '\Grocy\Controllers\CalendarController:overview');

	// OpenAPI routes
	$group->get('/api', '\Grocy\Controllers\OpenApiController:documentationUi');
	$group->get('/manageapikeys', '\Grocy\Controllers\OpenApiController:apiKeysList');
	$group->get('/manageapikeys/new', '\Grocy\Controllers\OpenApiController:createNewApiKey');
});

$app->group('/api', function (RouteCollectorProxy $group) {
	// OpenAPI
	$group->get('/openapi/specification', '\Grocy\Controllers\OpenApiController:documentationSpec');

	// System
	$group->get('/system/info', '\Grocy\Controllers\SystemApiController:getSystemInfo');
	$group->get('/system/time', '\Grocy\Controllers\SystemApiController:getSystemTime');
	$group->get('/system/db-changed-time', '\Grocy\Controllers\SystemApiController:getDbChangedTime');
	$group->get('/system/config', '\Grocy\Controllers\SystemApiController:getConfig');
	$group->post('/system/log-missing-localization', '\Grocy\Controllers\SystemApiController:logMissingLocalization');
	$group->get('/system/localization-strings', '\Grocy\Controllers\SystemApiController:getLocalizationStrings');

	// Generic entity interaction
	$group->get('/objects/{entity}', '\Grocy\Controllers\GenericEntityApiController:getObjects');
	$group->get('/objects/{entity}/{objectId}', '\Grocy\Controllers\GenericEntityApiController:getObject');
	$group->post('/objects/{entity}', '\Grocy\Controllers\GenericEntityApiController:addObject');
	$group->put('/objects/{entity}/{objectId}', '\Grocy\Controllers\GenericEntityApiController:editObject');
	$group->delete('/objects/{entity}/{objectId}', '\Grocy\Controllers\GenericEntityApiController:deleteObject');
	$group->get('/userfields/{entity}/{objectId}', '\Grocy\Controllers\GenericEntityApiController:getUserfields');
	$group->put('/userfields/{entity}/{objectId}', '\Grocy\Controllers\GenericEntityApiController:setUserfields');

	// Files
	$group->put('/files/{group}/{fileName}', '\Grocy\Controllers\FilesApiController:uploadFile');
	$group->get('/files/{group}/{fileName}', '\Grocy\Controllers\FilesApiController:serveFile');
	$group->delete('/files/{group}/{fileName}', '\Grocy\Controllers\FilesApiController:deleteFile');

	// Users
	$group->get('/users', '\Grocy\Controllers\UsersApiController:getUsers');
	$group->post('/users', '\Grocy\Controllers\UsersApiController:createUser');
	$group->put('/users/{userId}', '\Grocy\Controllers\UsersApiController:editUser');
	$group->delete('/users/{userId}', '\Grocy\Controllers\UsersApiController:deleteUser');
	$group->get('/users/{userId}/permissions', '\Grocy\Controllers\UsersApiController:listPermissions');
	$group->post('/users/{userId}/permissions', '\Grocy\Controllers\UsersApiController:addPermission');
	$group->put('/users/{userId}/permissions', '\Grocy\Controllers\UsersApiController:setPermissions');

	// User
	$group->get('/user', '\Grocy\Controllers\UsersApiController:currentUser');
	$group->get('/user/settings', '\Grocy\Controllers\UsersApiController:getUserSettings');
	$group->get('/user/settings/{settingKey}', '\Grocy\Controllers\UsersApiController:getUserSetting');
	$group->put('/user/settings/{settingKey}', '\Grocy\Controllers\UsersApiController:setUserSetting');
	$group->delete('/user/settings/{settingKey}', '\Grocy\Controllers\UsersApiController:deleteUserSetting');

	// Stock
	$group->get('/stock', '\Grocy\Controllers\StockApiController:currentStock');
	$group->get('/stock/entry/{entryId}', '\Grocy\Controllers\StockApiController:StockEntry');
	$group->put('/stock/entry/{entryId}', '\Grocy\Controllers\StockApiController:editStockEntry');
	$group->get('/stock/volatile', '\Grocy\Controllers\StockApiController:CurrentVolatileStock');
	$group->get('/stock/products/{productId}', '\Grocy\Controllers\StockApiController:ProductDetails');
	$group->get('/stock/products/{productId}/entries', '\Grocy\Controllers\StockApiController:ProductStockEntries');
	$group->get('/stock/products/{productId}/locations', '\Grocy\Controllers\StockApiController:ProductStockLocations');
	$group->get('/stock/products/{productId}/price-history', '\Grocy\Controllers\StockApiController:ProductPriceHistory');
	$group->post('/stock/products/{productId}/add', '\Grocy\Controllers\StockApiController:addProduct');
	$group->post('/stock/products/{productId}/consume', '\Grocy\Controllers\StockApiController:consumeProduct');
	$group->post('/stock/products/{productId}/transfer', '\Grocy\Controllers\StockApiController:transferProduct');
	$group->post('/stock/products/{productId}/inventory', '\Grocy\Controllers\StockApiController:inventoryProduct');
	$group->post('/stock/products/{productId}/open', '\Grocy\Controllers\StockApiController:openProduct');
	$group->post('/stock/products/{productIdToKeep}/merge/{productIdToRemove}', '\Grocy\Controllers\StockApiController:mergeProducts');
	$group->get('/stock/products/by-barcode/{barcode}', '\Grocy\Controllers\StockApiController:ProductDetailsByBarcode');
	$group->post('/stock/products/by-barcode/{barcode}/add', '\Grocy\Controllers\StockApiController:addProductByBarcode');
	$group->post('/stock/products/by-barcode/{barcode}/consume', '\Grocy\Controllers\StockApiController:consumeProductByBarcode');
	$group->post('/stock/products/by-barcode/{barcode}/transfer', '\Grocy\Controllers\StockApiController:transferProductByBarcode');
	$group->post('/stock/products/by-barcode/{barcode}/inventory', '\Grocy\Controllers\StockApiController:inventoryProductByBarcode');
	$group->post('/stock/products/by-barcode/{barcode}/open', '\Grocy\Controllers\StockApiController:openProductByBarcode');
	$group->get('/stock/locations/{locationId}/entries', '\Grocy\Controllers\StockApiController:LocationStockEntries');
	$group->get('/stock/bookings/{bookingId}', '\Grocy\Controllers\StockApiController:StockBooking');
	$group->post('/stock/bookings/{bookingId}/undo', '\Grocy\Controllers\StockApiController:undoBooking');
	$group->get('/stock/transactions/{transactionId}', '\Grocy\Controllers\StockApiController:StockTransactions');
	$group->post('/stock/transactions/{transactionId}/undo', '\Grocy\Controllers\StockApiController:undoTransaction');
	$group->get('/stock/barcodes/external-lookup/{barcode}', '\Grocy\Controllers\StockApiController:externalBarcodeLookup');
	$group->get('/stock/products/{productId}/printlabel', '\Grocy\Controllers\StockApiController:ProductPrintLabel');
	$group->get('/stock/entry/{entryId}/printlabel', '\Grocy\Controllers\StockApiController:StockEntryPrintLabel');

	// Shopping list
	$group->post('/stock/shoppinglist/add-missing-products', '\Grocy\Controllers\StockApiController:addMissingProductsToShoppingList');
	$group->post('/stock/shoppinglist/add-overdue-products', '\Grocy\Controllers\StockApiController:addOverdueProductsToShoppingList');
	$group->post('/stock/shoppinglist/add-expired-products', '\Grocy\Controllers\StockApiController:addExpiredProductsToShoppingList');
	$group->post('/stock/shoppinglist/clear', '\Grocy\Controllers\StockApiController:clearShoppingList');
	$group->post('/stock/shoppinglist/add-product', '\Grocy\Controllers\StockApiController:addProductToShoppingList');
	$group->post('/stock/shoppinglist/remove-product', '\Grocy\Controllers\StockApiController:removeProductFromShoppingList');

	// Recipes
	$group->post('/recipes/{recipeId}/add-not-fulfilled-products-to-shoppinglist', '\Grocy\Controllers\RecipesApiController:addNotFulfilledProductsToShoppingList');
	$group->get('/recipes/{recipeId}/fulfillment', '\Grocy\Controllers\RecipesApiController:getRecipeFulfillment');
	$group->post('/recipes/{recipeId}/consume', '\Grocy\Controllers\RecipesApiController:consumeRecipe');
	$group->get('/recipes/fulfillment', '\Grocy\Controllers\RecipesApiController:getRecipeFulfillment');
	$group->Post('/recipes/{recipeId}/copy', '\Grocy\Controllers\RecipesApiController:copyRecipe');
	$group->get('/recipes/{recipeId}/printlabel', '\Grocy\Controllers\RecipesApiController:recipePrintLabel');


	// Chores
	$group->get('/chores', '\Grocy\Controllers\ChoresApiController:Current');
	$group->get('/chores/{choreId}', '\Grocy\Controllers\ChoresApiController:choreDetails');
	$group->post('/chores/{choreId}/execute', '\Grocy\Controllers\ChoresApiController:trackChoreExecution');
	$group->post('/chores/executions/{executionId}/undo', '\Grocy\Controllers\ChoresApiController:undoChoreExecution');
	$group->post('/chores/executions/calculate-next-assignments', '\Grocy\Controllers\ChoresApiController:calculateNextExecutionAssignments');
	$group->get('/chores/{choreId}/printlabel', '\Grocy\Controllers\ChoresApiController:chorePrintLabel');
	$group->post('/chores/{choreIdToKeep}/merge/{choreIdToRemove}', '\Grocy\Controllers\ChoresApiController:mergeChores');

	//Printing
	$group->get('/print/shoppinglist/thermal', '\Grocy\Controllers\PrintApiController:printShoppingListThermal');

	// Batteries
	$group->get('/batteries', '\Grocy\Controllers\BatteriesApiController:current');
	$group->get('/batteries/{batteryId}', '\Grocy\Controllers\BatteriesApiController:batteryDetails');
	$group->post('/batteries/{batteryId}/charge', '\Grocy\Controllers\BatteriesApiController:trackChargeCycle');
	$group->post('/batteries/charge-cycles/{chargeCycleId}/undo', '\Grocy\Controllers\BatteriesApiController:undoChargeCycle');
	$group->get('/batteries/{batteryId}/printlabel', '\Grocy\Controllers\BatteriesApiController:batteryPrintLabel');

	// Tasks
	$group->get('/tasks', '\Grocy\Controllers\TasksApiController:Current');
	$group->post('/tasks/{taskId}/complete', '\Grocy\Controllers\TasksApiController:markTaskAsCompleted');
	$group->post('/tasks/{taskId}/undo', '\Grocy\Controllers\TasksApiController:undoTask');

	// Calendar
	$group->get('/calendar/ical', '\Grocy\Controllers\CalendarApiController:iCal')->setName('calendar-ical');
	$group->get('/calendar/ical/sharing-link', '\Grocy\Controllers\CalendarApiController:iCalSharingLink');
})->add(JsonMiddleware::class);

// Handle CORS preflight OPTIONS requests
$app->options('/api/{routes:.+}', function (Request $request, Response $response): Response {
	return $response->withStatus(204);
});

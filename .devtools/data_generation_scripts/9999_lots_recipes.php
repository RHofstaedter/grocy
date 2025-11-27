<?php

// This is executed inside DatabaseMigrationService class/context

use Grocy\Services\RecipesService;

$recipesService = RecipesService::getInstance();

for ($i = 1; $i <= 87; $i++) {
	$recipesService->copyRecipe(1);
	$recipesService->copyRecipe(2);
	$recipesService->copyRecipe(3);
	$recipesService->copyRecipe(4);
	$recipesService->copyRecipe(5);
}

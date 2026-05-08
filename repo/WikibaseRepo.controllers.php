<?php

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\ControllerRegistry;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\FallbackEntitySearchHelperController;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\ItemWbSearchEntitiesController;
use Wikibase\Repo\Domains\Search\WbSearch;
use Wikibase\Repo\WikibaseRepo;

/**
 * Controller callback definitions for built-in entity types.
 *
 * @note Avoid instantiating objects here! Use callbacks (closures) instead.
 *
 * @license GPL-2.0-or-later
 */

return [
	Item::ENTITY_TYPE => [
		ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER => static function () {
			return new ItemWbSearchEntitiesController(
				WbSearch::getItemPrefixSearch(),
				WikibaseRepo::getEntitySourceLookup(),
			);
		},
	],
	Property::ENTITY_TYPE => [
		ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER => static function (): FallbackEntitySearchHelperController {
			return new FallbackEntitySearchHelperController(
				Property::ENTITY_TYPE,
				WbSearch::getPropertySearchHelper(),
				WikibaseRepo::getEntitySourceLookup()
			);
		},
	],
];

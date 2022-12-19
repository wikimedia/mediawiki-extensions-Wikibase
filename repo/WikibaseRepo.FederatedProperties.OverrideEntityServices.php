<?php

/**
 * Definition of entity service overrides for Federated Properties.
 * The array returned by the code below is supposed to be merged with the content of
 * repo/WikibaseRepo.entitytypes.php.
 *
 * @note This is bootstrap code, it is executed for EVERY request.
 * Avoid instantiating objects here!
 *
 * @see docs/entitytypes.md
 *
 * @license GPL-2.0-or-later
 */

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Lib\Store\EntityArticleIdNullLookup;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Repo\WikibaseRepo;

return [
	'property' => [
		Def::ARTICLE_ID_LOOKUP_CALLBACK => function () {
			return new EntityArticleIdNullLookup();
		},
		Def::URL_LOOKUP_CALLBACK => function () {
			return WikibaseRepo::getFederatedPropertiesServiceFactory()->newApiEntityUrlLookup();
		},
		Def::TITLE_TEXT_LOOKUP_CALLBACK => function () {
			return WikibaseRepo::getFederatedPropertiesServiceFactory()->newApiEntityTitleTextLookup();
		},
		Def::ENTITY_SEARCH_CALLBACK => function() {
			return WikibaseRepo::getFederatedPropertiesServiceFactory()->newApiEntitySearchHelper();
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function() {
			return WikibaseRepo::getFederatedPropertiesServiceFactory()->newApiPrefetchingTermLookup();
		},
		Def::REDIRECT_CHECKER_CALLBACK => function () {
			return new class implements EntityRedirectChecker {
				public function isRedirect( EntityId $id ): bool {
					return false;
				}
			};
		},
		Def::EXISTENCE_CHECKER_CALLBACK => function () {
			return WikibaseRepo::getFederatedPropertiesServiceFactory()->newApiEntityExistenceChecker();
		},
	],
];

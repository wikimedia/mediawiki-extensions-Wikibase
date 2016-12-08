<?php

use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\WikibaseClient;

/**
 * @license GPL-2.0+
 */

return [

	'EntityRevisionLookup' => function (
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		$entityRevisionLookupFactory = $client->getEntityRevisionLookupFactory();
		return $entityRevisionLookupFactory->getLookup( $services->getRepositoryName() );
	},

];

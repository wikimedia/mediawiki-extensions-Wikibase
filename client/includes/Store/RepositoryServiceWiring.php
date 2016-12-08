<?php

use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\WikibaseClient;
use Wikibase\TermSqlIndex;

/**
 * @license GPL-2.0+
 */

return [

	'EntityRevisionLookup' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		$entityRevisionLookupFactory = $client->getEntityRevisionLookupFactory();
		return $entityRevisionLookupFactory->getLookup( $services->getRepositoryName() );
	},

	'TermIndex' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		return new TermSqlIndex(
			$client->getStringNormalizer(),
			$client->getEntityIdComposer(),
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);
	}

];

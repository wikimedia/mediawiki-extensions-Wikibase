<?php

use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Lib\Store\RepositorySpecificEntityRevisionLookupFactory;

/**
 * @license GPL-2.0+
 */

return [

	// TODO: should additional instantiation params as $entityRevisionLookupFactory should probably come wrapped
	// in the array  or so? The list of those parameter will grow as more service are added here.
	// Plus assuming the order and content of the list of additional parameters seems contradicting the idea
	// of having customizable wiring files
	'EntityRevisionLookup' => function (
		RepositoryServiceContainer $services,
		RepositorySpecificEntityRevisionLookupFactory $entityRevisionLookupFactory
	) {
		return $entityRevisionLookupFactory->getLookup( $services->getRepositoryName() );
	},

];

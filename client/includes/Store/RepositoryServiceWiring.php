<?php

use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Lib\Store\RepositorySpecificEntityRevisionLookupFactory;

return [

	'EntityRevisionLookup' => function (
		RepositoryServiceContainer $services,
		RepositorySpecificEntityRevisionLookupFactory $entityRevisionLookupFactory
	) {
		return $entityRevisionLookupFactory->getLookup( $services->getRepositoryName() );
	},

];
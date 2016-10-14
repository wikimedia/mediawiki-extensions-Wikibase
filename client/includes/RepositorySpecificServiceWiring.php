<?php

use Wikibase\Client\RepositorySpecificServices;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;

return [

	// TODO: do we really need this?
	'EntityLookups' => function ( RepositorySpecificServices $services ) {
		$lookupFactory = $services->getRepositorySpecificEntityRevisionLookupFactory();
		$lookups = [];
		foreach ( $services->getRepositoryNames() as $repoName ) {
			$lookups[$repoName] = new RedirectResolvingEntityLookup(
				new RevisionBasedEntityLookup(
					$lookupFactory->getLookup( $repoName )
				)
			);
		}

		return $lookups;
	},

	'EntityRevisionLookups' => function ( RepositorySpecificServices $services ) {
		$lookupFactory = $services->getRepositorySpecificEntityRevisionLookupFactory();
		$lookups = [];
		foreach ( $services->getRepositoryNames() as $repoName ) {
			$lookups[$repoName] = $lookupFactory->getLookup( $repoName );
		}
		return $lookups;
	},

];

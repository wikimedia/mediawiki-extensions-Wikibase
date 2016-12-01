<?php

use Wikibase\Client\RepositorySpecificServices;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\TermSqlIndex;

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

	'TermBuffers' => function ( RepositorySpecificServices $services ) {
		$databaseNames = $services->getDatabaseNames();
		$buffers = [];
		foreach ( $services->getRepositoryNames() as $repositoryName ) {
			$buffers[$repositoryName] = new BufferingTermLookup(
				new TermSqlIndex(
					$services->getClient()->getStringNormalizer(),
					$services->getClient()->getEntityIdComposer(),
					$databaseNames[$repositoryName],
					$repositoryName
				),
				1000 // @todo: configure buffer size (TODO originally from WikibaseClient)
			);
		}
		return $buffers;
	},

];

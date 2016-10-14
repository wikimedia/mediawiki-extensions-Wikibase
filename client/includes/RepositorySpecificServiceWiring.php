<?php

use Wikibase\Client\RepositorySpecificServices;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\DispatchingTermLookup;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Store\BufferingTermLookup;

return [
	'EntityLookup' => function ( RepositorySpecificServices $services ) {
		$foreignRepositoryLookups = [];
		foreach ( $services->getForeignRepositories() as $repoName ) {
			$foreignRepositoryLookups[$repoName] = $services->getClient()->getStore( $repoName )->getEntityLookup();
		}

		return new DispatchingEntityLookup(
			array_merge(
				[ '' => $services->getClient()->getStore( '' )->getEntityLookup() ],
				$foreignRepositoryLookups
			),
			$services->getClient()->getEntityIdParser()
		);
	},

	'EntityRevisionLookup' => function ( RepositorySpecificServices $services ) {
		$foreignRepositoryLookups = [];
		foreach ( $services->getForeignRepositories() as $repoName ) {
			$foreignRepositoryLookups[$repoName] = $services->getClient()->getStore( $repoName )->getEntityRevisionLookup();
		}
		return new DispatchingEntityRevisionLookup(
			array_merge(
				[ '' => $services->getClient()->getStore( '' )->getEntityRevisionLookup() ],
				$foreignRepositoryLookups
			),
			$services->getClient()->getEntityIdParser()
		);
	},

	'TermLookup' => function ( RepositorySpecificServices $services ) {
		$localLookup = new BufferingTermLookup(
			$services->getClient()->getStore( '' )->getTermIndex(),
			1000 // @todo: configure buffer size
		);
		$foreignRepositoryLookups = [];
		foreach ( $services->getForeignRepositories() as $repoName ) {
			$foreignRepositoryLookups[$repoName] = new BufferingTermLookup(
				$services->getClient()->getStore( '' )->getTermIndex(),
				1000 // @todo: configure buffer size
			);
		}
		return new DispatchingTermLookup(
			array_merge( [ '' => $localLookup ], $foreignRepositoryLookups ),
			$services->getClient()->getEntityIdParser()
		);
	},
];

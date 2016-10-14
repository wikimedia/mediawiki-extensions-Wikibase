<?php

use Wikibase\Client\RepositorySpecificServices;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\DispatchingTermLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\ForeignEntityRevisionLookupFactory;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;

return [
	'EntityLookup' => function ( RepositorySpecificServices $services ) {
		$lookupFactory = new ForeignEntityRevisionLookupFactory(
			new PrefixMappingEntityIdParserFactory(
				$services->getClient()->getEntityIdParser(),
				$services->getPrefixMapping()
			),
			$services->getClient()->getDataValueDeserializer(),
			$services->getClient()->getEntityIdParser(),
			$services->getClient()->getEntityNamespaceLookup(),
			$services->getClient()->getSettings()->getSetting( 'maxSerializedEntitySize' ) * 1024,
			$services->getDatabaseNames()
		);
		$foreignRepositoryLookups = [];
		foreach ( $services->getForeignRepositories() as $repoName ) {
			$foreignRepositoryLookups[$repoName] = new RedirectResolvingEntityLookup(
				new RevisionBasedEntityLookup(
					$lookupFactory->getLookup( $repoName )
				)
			);
		}

		return new DispatchingEntityLookup(
			array_merge(
				[ '' => $services->getClient()->getStore( '' )->getEntityLookup() ],
				$foreignRepositoryLookups
			)
		);
	},

	'EntityRevisionLookup' => function ( RepositorySpecificServices $services ) {
		$lookupFactory = new ForeignEntityRevisionLookupFactory(
			new PrefixMappingEntityIdParserFactory(
				$services->getClient()->getEntityIdParser(),
				$services->getPrefixMapping()
			),
			$services->getClient()->getDataValueDeserializer(),
			$services->getClient()->getEntityIdParser(),
			$services->getClient()->getEntityNamespaceLookup(),
			$services->getClient()->getSettings()->getSetting( 'maxSerializedEntitySize' ) * 1024,
			$services->getDatabaseNames()
		);
		$foreignRepositoryLookups = [];
		foreach ( $services->getForeignRepositories() as $repoName ) {
			$foreignRepositoryLookups[$repoName] = $lookupFactory->getLookup( $repoName );
		}
		return new DispatchingEntityRevisionLookup(
			array_merge(
				[ '' => $services->getClient()->getStore( '' )->getEntityRevisionLookup() ],
				$foreignRepositoryLookups
			)
		);
	},

];

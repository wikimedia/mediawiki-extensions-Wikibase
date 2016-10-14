<?php

use Wikibase\Client\ForbiddenSerializer;
use Wikibase\Client\RepositorySpecificServices;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\ForeignEntityRevisionLookupFactory;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;

return [
	'DataValueDeserializerFactory' => function( RepositorySpecificServices $services ) {
		return new RepositorySpecificDataValueDeserializerFactory(
			$services->getPrefixMappingEntityIdParserFactory()
		);
	},

	'EntityLookup' => function ( RepositorySpecificServices $services ) {
		$lookupFactory = new ForeignEntityRevisionLookupFactory(
			$services->getPrefixMappingEntityIdParserFactory(),
			new ForbiddenSerializer( 'Entity serialization is not supported on the client!' ),
			$services->getDataValueDeserializerFactory(),
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
			$services->getPrefixMappingEntityIdParserFactory(),
			new ForbiddenSerializer( 'Entity serialization is not supported on the client!' ),
			$services->getDataValueDeserializerFactory(),
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

	'PrefixMappingEntityIdParserFactory' => function ( RepositorySpecificServices $services ) {
		return new PrefixMappingEntityIdParserFactory(
			$services->getClient()->getEntityIdParser(),
			$services->getPrefixMapping()
		);
	}

];

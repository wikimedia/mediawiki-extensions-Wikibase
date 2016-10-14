<?php

use Wikibase\Client\ForbiddenSerializer;
use Wikibase\Client\RepositorySpecificServices;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\RepositorySpecificEntityRevisionLookupFactory;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;

return [
	'DataValueDeserializerFactory' => function( RepositorySpecificServices $services ) {
		return new RepositorySpecificDataValueDeserializerFactory(
			$services->getPrefixMappingEntityIdParserFactory()
		);
	},

	'EntityLookup' => function ( RepositorySpecificServices $services ) {
		$lookupFactory = new RepositorySpecificEntityRevisionLookupFactory(
			$services->getPrefixMappingEntityIdParserFactory(),
			new ForbiddenSerializer( 'Entity serialization is not supported on the client!' ),
			$services->getDataValueDeserializerFactory(),
			[
				'item' => function( DeserializerFactory $deserializerFactory ) {
					return $deserializerFactory->newItemDeserializer();
				},
				'property' => function( DeserializerFactory $deserializerFactory ) {
					return $deserializerFactory->newPropertyDeserializer();
				}
			],
			$services->getClient()->getEntityNamespaceLookup(),
			$services->getClient()->getSettings()->getSetting( 'maxSerializedEntitySize' ) * 1024,
			$services->getDatabaseNames()
		);
		$lookups = [];
		foreach ( $services->getRepositoryNames() as $repoName ) {
			$lookups[$repoName] = new RedirectResolvingEntityLookup(
				new RevisionBasedEntityLookup(
					$lookupFactory->getLookup( $repoName )
				)
			);
		}

		return new DispatchingEntityLookup(
			$lookups
		);
	},

	'EntityRevisionLookup' => function ( RepositorySpecificServices $services ) {
		$lookupFactory = new RepositorySpecificEntityRevisionLookupFactory(
			$services->getPrefixMappingEntityIdParserFactory(),
			new ForbiddenSerializer( 'Entity serialization is not supported on the client!' ),
			$services->getDataValueDeserializerFactory(),
			[
				'item' => function( DeserializerFactory $deserializerFactory ) {
					return $deserializerFactory->newItemDeserializer();
				},
				'property' => function( DeserializerFactory $deserializerFactory ) {
					return $deserializerFactory->newPropertyDeserializer();
				}
			],
			$services->getClient()->getEntityNamespaceLookup(),
			$services->getClient()->getSettings()->getSetting( 'maxSerializedEntitySize' ) * 1024,
			$services->getDatabaseNames()
		);
		$lookups = [];
		foreach ( $services->getRepositoryNames() as $repoName ) {
			$lookups[$repoName] = $lookupFactory->getLookup( $repoName );
		}
		return new DispatchingEntityRevisionLookup(
			$lookups
		);
	},

	'PrefixMappingEntityIdParserFactory' => function ( RepositorySpecificServices $services ) {
		return new PrefixMappingEntityIdParserFactory(
			$services->getClient()->getEntityIdParser(),
			$services->getPrefixMapping()
		);
	}

];

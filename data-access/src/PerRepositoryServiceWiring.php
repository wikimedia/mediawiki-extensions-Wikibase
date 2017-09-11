<?php

use Wikibase\DataAccess\Serializer\ForbiddenSerializer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\PerRepositoryServiceContainer;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilderFactory;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\TermIndex;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\WikibaseSettings;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */

return [

	'EntityInfoBuilderFactory' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		$factory = new SqlEntityInfoBuilderFactory(
			$services->getEntityIdParser(),
			$services->getEntityIdComposer(),
			$genericServices->getEntityNamespaceLookup(),
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);

		$factory->setReadFullEntityIdColumn( $settings->readFullEntityIdColumn() );

		return $factory;
	},

	'EntityPrefetcher' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices
	) {
		$prefetcher = $services->getService( 'WikiPageEntityMetaDataAccessor' );

		Assert::postcondition(
			$prefetcher instanceof EntityPrefetcher,
			'The WikiPageEntityMetaDataAccessor service is expected to implement EntityPrefetcher interface.'
		);

		return $prefetcher;
	},

	'EntityRevisionLookup' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$serializer = new ForbiddenSerializer( 'Entity serialization is not supported on the client!' );
		} elseif ( $services->getRepositoryName() !== '' ) {
			$serializer = new ForbiddenSerializer( 'Serialization of foreign entities is not supported!' );
		} else {
			$serializer = $genericServices->getStorageEntitySerializer();
		}

		$codec = new EntityContentDataCodec(
			$services->getEntityIdParser(),
			$serializer,
			$services->getEntityDeserializer(),
			$settings->maxSerializedEntitySizeInBytes()
		);

		/** @var WikiPageEntityMetaDataAccessor $metaDataAccessor */
		$metaDataAccessor = $services->getService( 'WikiPageEntityMetaDataAccessor' );

		return new WikiPageEntityRevisionLookup(
			$codec,
			$metaDataAccessor,
			$services->getDatabaseName()
		);
	},

	'PrefetchingTermLookup' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices
	) {
		/** @var TermIndex $termIndex */
		$termIndex = $services->getService( 'TermIndex' );

		return new BufferingTermLookup( $termIndex, 1000 ); // TODO: customize buffer sizes
	},

	'PropertyInfoLookup' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices
	) {
		return new PropertyInfoTable(
			$services->getEntityIdComposer(),
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);
	},

	'TermBuffer' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices
	) {
		return $services->getService( 'PrefetchingTermLookup' );
	},

	'TermIndex' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		$index = new TermSqlIndex(
			$genericServices->getStringNormalizer(),
			$services->getEntityIdComposer(),
			$services->getEntityIdParser(),
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);
		$index->setReadFullEntityIdColumn( $settings->readFullEntityIdColumn() );
		return $index;
	},

	'TermSearchInteractorFactory' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices
	) {
		/** @var TermIndex $termIndex */
		$termIndex = $services->getService( 'TermIndex' );
		/** @var PrefetchingTermLookup $prefetchingTermLookup */
		$prefetchingTermLookup = $services->getService( 'PrefetchingTermLookup' );

		return new TermIndexSearchInteractorFactory(
			$termIndex,
			$genericServices->getLanguageFallbackChainFactory(),
			$prefetchingTermLookup
		);
	},

	'WikiPageEntityMetaDataAccessor' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices
	) {
		return new PrefetchingWikiPageEntityMetaDataAccessor(
			new WikiPageEntityMetaDataLookup(
				$genericServices->getEntityNamespaceLookup(),
				$services->getDatabaseName(),
				$services->getRepositoryName()
			)
		);
	},

];

<?php

use Wikibase\Client\Serializer\ForbiddenSerializer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\RepositoryServiceContainer;
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
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */

return [

	'EntityInfoBuilderFactory' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		$factory = new SqlEntityInfoBuilderFactory(
			$services->getEntityIdParser(),
			$services->getEntityIdComposer(),
			$client->getEntityNamespaceLookup(),
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);

		$factory->setReadFullEntityIdColumn( $client->getSettings()->getSetting( 'readFullEntityIdColumn' ) );

		return $factory;
	},

	'EntityPrefetcher' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		$prefetcher = $services->getService( 'WikiPageEntityMetaDataAccessor' );

		Assert::postcondition(
			$prefetcher instanceof EntityPrefetcher,
			'The WikiPageEntityMetaDataAccessor service is expected to implement EntityPrefetcher interface.'
		);

		return $prefetcher;
	},

	'EntityRevisionLookup' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		$codec = new EntityContentDataCodec(
			$services->getEntityIdParser(),
			new ForbiddenSerializer( 'Entity serialization is not supported on the client!' ),
			$services->getEntityDeserializer(),
			$client->getSettings()->getSetting( 'maxSerializedEntitySize' ) * 1024
		);

		/** @var WikiPageEntityMetaDataAccessor $metaDataAccessor */
		$metaDataAccessor = $services->getService( 'WikiPageEntityMetaDataAccessor' );

		return new WikiPageEntityRevisionLookup(
			$codec,
			$metaDataAccessor,
			$services->getDatabaseName()
		);
	},

	'PrefetchingTermLookup' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		/** @var TermIndex $termIndex */
		$termIndex = $services->getService( 'TermIndex' );

		return new BufferingTermLookup( $termIndex, 1000 ); // TODO: customize buffer sizes
	},

	'PropertyInfoLookup' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		return new PropertyInfoTable(
			$services->getEntityIdComposer(),
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);
	},

	'TermBuffer' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		return $services->getService( 'PrefetchingTermLookup' );
	},

	'TermIndex' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		$index = new TermSqlIndex(
			$client->getStringNormalizer(),
			$services->getEntityIdComposer(),
			$services->getEntityIdParser(),
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);
		$index->setReadFullEntityIdColumn( $client->getSettings()->getSetting( 'readFullEntityIdColumn' ) );
		return $index;
	},

	'TermSearchInteractorFactory' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		/** @var TermIndex $termIndex */
		$termIndex = $services->getService( 'TermIndex' );
		/** @var PrefetchingTermLookup $prefetchingTermLookup */
		$prefetchingTermLookup = $services->getService( 'PrefetchingTermLookup' );

		return new TermIndexSearchInteractorFactory(
			$termIndex,
			$client->getLanguageFallbackChainFactory(),
			$prefetchingTermLookup
		);
	},

	'WikiPageEntityMetaDataAccessor' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		return new PrefetchingWikiPageEntityMetaDataAccessor(
			new WikiPageEntityMetaDataLookup(
				$client->getEntityNamespaceLookup(),
				$services->getDatabaseName(),
				$services->getRepositoryName()
			)
		);
	},

];

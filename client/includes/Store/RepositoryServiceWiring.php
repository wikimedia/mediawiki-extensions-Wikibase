<?php

use Wikibase\Client\ForbiddenSerializer;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\WikiPageEntityRevisionLookup;
use Wikibase\TermSqlIndex;

/**
 * @license GPL-2.0+
 */

return [

	'EntityPrefetcher' => function(
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

		/** @var PrefetchingWikiPageEntityMetaDataAccessor $metaDataAccessor */
		$metaDataAccessor = $services->getService( 'EntityPrefetcher' );

		return new WikiPageEntityRevisionLookup(
			$codec,
			$metaDataAccessor,
			$services->getDatabaseName()
		);
	},

	'PropertyInfoLookup' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		return new PropertyInfoTable(
			$client->getEntityIdComposer(),
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);
	},

	'TermIndex' => function(
		RepositoryServiceContainer $services,
		WikibaseClient $client
	) {
		return new TermSqlIndex(
			$client->getStringNormalizer(),
			$client->getEntityIdComposer(),
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);
	}

];

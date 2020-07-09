<?php

/**
 * Definition of base entity types for use with Wikibase.
 *
 * @note: When adding entity types here, also add the corresponding information to
 * repo/WikibaseRepo.entitytypes.php
 *
 * @note This is bootstrap code, it is executed for EVERY request.
 * Avoid instantiating objects here!
 *
 * @see docs/entitytypes.wiki
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

use MediaWiki\MediaWikiServices;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Diff\ItemDiffer;
use Wikibase\DataModel\Services\Diff\ItemPatcher;
use Wikibase\DataModel\Services\Diff\PropertyDiffer;
use Wikibase\DataModel\Services\Diff\PropertyPatcher;
use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;
use Wikibase\Lib\Store\CachingPrefetchingTermLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;
use Wikibase\Lib\Store\Sql\Terms\TermStoresDelegatingPrefetchingItemTermLookup;
use Wikibase\Lib\Store\UncachedTermsPrefetcher;
use Wikibase\Lib\WikibaseContentLanguages;

return [
	'item' => [
		Def::SERIALIZER_FACTORY_CALLBACK => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newItemSerializer();
		},
		Def::DESERIALIZER_FACTORY_CALLBACK => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newItemDeserializer();
		},
		Def::ENTITY_ID_PATTERN => ItemId::PATTERN,
		Def::ENTITY_ID_BUILDER => function( $serialization ) {
			return new ItemId( $serialization );
		},
		Def::ENTITY_ID_COMPOSER_CALLBACK => function( $repositoryName, $uniquePart ) {
			return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
		},
		Def::ENTITY_DIFFER_STRATEGY_BUILDER => function() {
			return new ItemDiffer();
		},
		Def::ENTITY_PATCHER_STRATEGY_BUILDER => function() {
			return new ItemPatcher();
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function( SingleEntitySourceServices $entitySourceServices ) {
			$termIdsResolver = $entitySourceServices->getTermInLangIdsResolver();

			return new TermStoresDelegatingPrefetchingItemTermLookup(
				$entitySourceServices->getDataAccessSettings(),
				new PrefetchingItemTermLookup( $termIdsResolver ),
				$entitySourceServices->getTermIndexPrefetchingTermLookup()
			);
		},
	],
	'property' => [
		Def::SERIALIZER_FACTORY_CALLBACK => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newPropertySerializer();
		},
		Def::DESERIALIZER_FACTORY_CALLBACK => function( DeserializerFactory $deserializerFactory ) {
			return $deserializerFactory->newPropertyDeserializer();
		},
		Def::ENTITY_ID_PATTERN => PropertyId::PATTERN,
		Def::ENTITY_ID_BUILDER => function( $serialization ) {
			return new PropertyId( $serialization );
		},
		Def::ENTITY_ID_COMPOSER_CALLBACK => function( $repositoryName, $uniquePart ) {
			return PropertyId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
		},
		Def::ENTITY_DIFFER_STRATEGY_BUILDER => function() {
			return new PropertyDiffer();
		},
		Def::ENTITY_PATCHER_STRATEGY_BUILDER => function() {
			return new PropertyPatcher();
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function( SingleEntitySourceServices $entitySourceServices ) {
			global $wgSecretKey;

			// Legacy wb_terms mode
			if ( !$entitySourceServices->getDataAccessSettings()->useNormalizedPropertyTerms() ) {
				return $entitySourceServices->getTermIndexPrefetchingTermLookup();
			}

			$mwServices = MediaWikiServices::getInstance();
			$cacheSecret = hash( 'sha256', $wgSecretKey );
			$bagOStuff = $mwServices->getLocalServerObjectCache();

			$prefetchingPropertyTermLookup = new PrefetchingPropertyTermLookup(
				$entitySourceServices->getTermInLangIdsResolver()
			);

			// If MediaWiki has no local server cache available, return the raw lookup.
			if ( $bagOStuff instanceof EmptyBagOStuff ) {
				return $prefetchingPropertyTermLookup;
			}

			$cache = new SimpleCacheWithBagOStuff(
				$bagOStuff,
				'wikibase.prefetchingPropertyTermLookup.',
				$cacheSecret
			);
			$cache = new StatsdRecordingSimpleCache(
				$cache,
				$mwServices->getStatsdDataFactory(),
				[
					'miss' => 'wikibase.prefetchingPropertyTermLookupCache.miss',
					'hit' => 'wikibase.prefetchingPropertyTermLookupCache.hit'
				]
			);
			$redirectResolvingRevisionLookup = new RedirectResolvingLatestRevisionLookup( $entitySourceServices->getEntityRevisionLookup() );

			return new CachingPrefetchingTermLookup(
				$cache,
				new UncachedTermsPrefetcher(
					$prefetchingPropertyTermLookup,
					$redirectResolvingRevisionLookup,
					60 // 1 minute ttl
				),
				$redirectResolvingRevisionLookup,
				WikibaseContentLanguages::getDefaultInstance()->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
			);
		},
	]
];

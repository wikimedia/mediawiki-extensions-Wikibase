<?php

declare( strict_types=1 );

/**
 * Definition of entity types for use with Wikibase.
 * The array returned by the code below is supposed to be merged with the content of
 * lib/WikibaseLib.entitytypes.php.
 *
 * @note: Keep in sync with lib/WikibaseLib.entitytypes.php
 *
 * @note This is bootstrap code, it is executed for EVERY request.
 * Avoid instantiating objects here!
 *
 * @see docs/entitytypes.wiki
 *
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;
use Wikibase\Lib\Store\CachingPrefetchingTermLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;

return [
	'item' => [
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function ( DatabaseEntitySource $entitySource ) {
			$termIdsResolver = WikibaseClient::getTermInLangIdsResolverFactory()
				->getResolverForEntitySource( $entitySource );

			return new PrefetchingItemTermLookup( $termIdsResolver );
		},
	],
	'property' => [
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function ( DatabaseEntitySource $entitySource ) {
			$mwServices = MediaWikiServices::getInstance();

			$cacheSecret = hash( 'sha256', $mwServices->getMainConfig()->get( 'SecretKey' ) );
			$bagOStuff = $mwServices->getLocalServerObjectCache();
			$termIdsResolver = WikibaseClient::getTermInLangIdsResolverFactory()
				->getResolverForEntitySource( $entitySource );

			$prefetchingPropertyTermLookup = new PrefetchingPropertyTermLookup( $termIdsResolver );

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
					'hit' => 'wikibase.prefetchingPropertyTermLookupCache.hit',
				]
			);

			return new CachingPrefetchingTermLookup(
				$cache,
				$prefetchingPropertyTermLookup,
				WikibaseClient::getRedirectResolvingLatestRevisionLookup( $mwServices ),
				WikibaseClient::getTermsLanguages( $mwServices )
			);
		},
	],
];

<?php

declare( strict_types = 1 );

namespace Wikibase\View;

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class FallbackChainModuleMethods {

	public static function buildFallbackChains(): array {
		$services = MediaWikiServices::getInstance();
		if ( !WikibaseRepo::getSettings( $services )->getSetting( 'tmpEnableMulLanguageCode' ) ) {
			return [];
		}
		$localCache = $services->getLocalServerObjectCache();
		return $localCache->getWithSetCallback(
			$localCache->makeKey( 'wikibase-fallback-chains' ),
			$localCache::TTL_WEEK,
			function () use ( $services ) {
				$startTime = microtime( true );

				$fallbackChainFactory = WikibaseRepo::getLanguageFallbackChainFactory( $services );
				$languages = WikibaseRepo::getTermsLanguages( $services )->getLanguages();

				$chains = [];
				foreach ( $languages as $language ) {
					$chains[$language] = $fallbackChainFactory->newFromLanguageCode( $language )->getFetchLanguageCodes();
				}

				$endTime = microtime( true );
				$statsdFactory = $services->getStatsdDataFactory();
				$statsdFactory->timing( 'wikibase.view.fallbackchains.timing', ( $endTime - $startTime ) * 1000 );
				return $chains;
			}
		);
	}
}

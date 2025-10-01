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
		if ( !WikibaseRepo::getSettings( $services )->getSetting( 'enableMulLanguageCode' ) ) {
			return [];
		}
		$localCache = $services->getLocalServerObjectCache();
		return $localCache->getWithSetCallback(
			$localCache->makeKey( 'wikibase-fallback-chains' ),
			$localCache::TTL_WEEK,
			function () use ( $services ) {
				$statsFactory = $services->getStatsFactory()->withComponent( 'WikibaseRepo' );
				$timing = $statsFactory
					->getTiming( 'fallbackchains_timing_seconds' )
					->start();

				$fallbackChainFactory = WikibaseRepo::getLanguageFallbackChainFactory( $services );
				$languages = WikibaseRepo::getTermsLanguages( $services )->getLanguages();

				$chains = [];
				foreach ( $languages as $language ) {
					$chains[$language] = $fallbackChainFactory->newFromLanguageCode( $language )->getFetchLanguageCodes();
				}

				$timing->stop();

				return $chains;
			}
		);
	}
}

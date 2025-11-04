<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\WikibaseRepo;

/**
 * Hook handlers for the Federated Values "package" inside WikibaseRepo.
 */
class FederatedValuesHooks {

	/**
	 * Hook: WikibaseRepoEntitySearchHelperCallbacks
	 *
	 * @param array<string,callable> &$callbacks
	 */
	public static function onWikibaseRepoEntitySearchHelperCallbacks( array &$callbacks ): void {
		$services = MediaWikiServices::getInstance();
		$settings = WikibaseRepo::getSettings( $services );

		// Only touch things if the feature is configured as enabled
		if (
			!$settings->hasSetting( 'federatedValuesEnabled' ) ||
			!$settings->getSetting( 'federatedValuesEnabled' )
		) {
			return;
		}

		// We only care about the "item" search helper
		if ( !isset( $callbacks['item'] ) ) {
			return;
		}

		$originalItemFactory = $callbacks['item'];

		// Wrap the existing item factory with our decorator.
		// Use ...$args so we don't need to know the exact factory signature.
		$callbacks['item'] = static function ( ...$args ) use ( $originalItemFactory ) {
			$services = MediaWikiServices::getInstance();

			$innerHelper = $originalItemFactory( ...$args );

			$remoteSearchClient = $services->getService(
				'WikibaseRepo.FederatedValues.RemoteSearchClient'
			);

			$settings = WikibaseRepo::getSettings( $services );

			return new FederatedValuesEntitySearchHelper(
				$innerHelper,
				$remoteSearchClient,
				$settings
			);
		};
	}
}

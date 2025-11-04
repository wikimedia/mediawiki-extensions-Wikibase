<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\WikibaseRepo;

/**
 * Hook handlers for the generic federation "package" inside WikibaseRepo.
 *
 * This decorates EntitySearchHelper instances for configured entity types
 * so that they include remote (federated) search results.
 */
class FederationHooks {

	/**
	 * Hook: WikibaseRepoEntitySearchHelperCallbacks
	 *
	 * @param array<string,callable> &$callbacks
	 */
	public static function onWikibaseRepoEntitySearchHelperCallbacks( array &$callbacks ): void {
		$services = MediaWikiServices::getInstance();
		$settings = WikibaseRepo::getSettings( $services );

		// If federation is globally disabled, don't touch anything.
		if ( !$settings->getSetting( 'federationEnabled' ) ) {
			return;
		}

		$federatedTypes = $settings->hasSetting( 'federationForEntityTypes' )
			? $settings->getSetting( 'federationForEntityTypes' )
			: [ 'item' ];

		if ( !is_array( $federatedTypes ) || $federatedTypes === [] ) {
			return;
		}

		foreach ( $federatedTypes as $entityType ) {
			if ( !isset( $callbacks[$entityType] ) ) {
				// Nothing registered for this type; skip it.
				continue;
			}

			$originalFactory = $callbacks[$entityType];

			// Wrap the existing factory with our decorator.
			// Use ...$args so we don't need to know the exact factory signature.
			$callbacks[$entityType] = static function ( ...$args ) use ( $originalFactory, $entityType ) {
				$services = MediaWikiServices::getInstance();
				$innerHelper = $originalFactory( ...$args );

				// Adjust this service name to whatever you used in your service wiring
				// (e.g. WikibaseRepo.Federation.RemoteEntitySearchClient).
				$remoteEntitySearchClient = $services->getService(
					'WikibaseRepo.Federation.RemoteEntitySearchClient'
				);

				$settings = WikibaseRepo::getSettings( $services );

				return new RemoteEntitySearchHelper(
					$innerHelper,
					$remoteEntitySearchClient,
					$settings
				);
			};
		}
	}
}

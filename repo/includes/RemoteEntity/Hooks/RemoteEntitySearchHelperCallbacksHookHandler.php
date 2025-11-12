<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity\Hooks;

use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\RemoteEntity\RemoteEntitySearchClient;
use Wikibase\Repo\RemoteEntity\RemoteEntitySearchHelper;
use Wikibase\Repo\Hooks\WikibaseRepoEntitySearchHelperCallbacksHook;

class RemoteEntitySearchHelperCallbacksHookHandler implements WikibaseRepoEntitySearchHelperCallbacksHook {

	private RemoteEntitySearchClient $remoteClient;
	private SettingsArray $settings;

	public function __construct(
		RemoteEntitySearchClient $remoteClient,
		SettingsArray $settings
	) {
		$this->remoteClient = $remoteClient;
		$this->settings = $settings;
	}

	/**
	 * @param array<string,callable> &$callbacks
	 */
	public function onWikibaseRepoEntitySearchHelperCallbacks( array &$callbacks ): void {
		if ( !$this->settings->getSetting( 'federationEnabled' ) ) {
			return;
		}

		$remoteClient = $this->remoteClient;
		$settings = $this->settings;

		foreach ( $callbacks as $entityType => $localSearchHelperFactory ) {
			// Wrap the existing factory with our decorator.
			$callbacks[$entityType] = static function ( ...$args ) use ( $localSearchHelperFactory, $remoteClient, $settings ) {
				$localSearchHelper = $localSearchHelperFactory( ...$args );

				return new RemoteEntitySearchHelper(
					$localSearchHelper,
					$remoteClient,
					$settings
				);
			};
		}
	}
}

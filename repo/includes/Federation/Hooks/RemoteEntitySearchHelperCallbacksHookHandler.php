<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation\Hooks;

use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Federation\RemoteEntitySearchClient;
use Wikibase\Repo\Federation\RemoteEntitySearchHelper;
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

		foreach ( $callbacks as $entityType => $originalFactory ) {
			// Wrap the existing factory with our decorator.
			$callbacks[$entityType] = static function ( ...$args ) use ( $originalFactory, $remoteClient, $settings ) {
				$innerHelper = $originalFactory( ...$args );

				return new RemoteEntitySearchHelper(
					$innerHelper,
					$remoteClient,
					$settings
				);
			};
		}
	}
}

<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Preferences\Hook\GetPreferencesHook;
use Wikibase\Lib\SettingsArray;

/**
 * Adds a preference for showing or hiding Wikidata entries in recent changes
 *
 * @license GPL-2.0-or-later
 */
class GetPreferencesHandler implements GetPreferencesHook {

	private SettingsArray $clientSettings;

	public function __construct( SettingsArray $clientSettings ) {
		$this->clientSettings = $clientSettings;
	}

	/** @inheritDoc */
	public function onGetPreferences( $user, &$preferences ) {
		if ( !$this->clientSettings->getSetting( 'showExternalRecentChanges' ) ) {
			return;
		}

		$preferences['rcshowwikidata'] = [
			'type' => 'toggle',
			'label-message' => 'wikibase-rc-show-wikidata-pref',
			'section' => 'rc/advancedrc',
		];

		$preferences['wlshowwikibase'] = [
			'type' => 'toggle',
			'label-message' => 'wikibase-watchlist-show-changes-pref',
			'section' => 'watchlist/advancedwatchlist',
		];
	}

}

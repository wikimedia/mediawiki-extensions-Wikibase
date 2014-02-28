<?php

namespace Wikibase;

use FormOptions;
use Wikibase\Client\WikibaseClient;

/**
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RecentChangesFilterOptions {

	protected $opts;

	public function __construct( FormOptions $opts ) {
		$this->opts = $opts;
	}

	/**
	 * Is Wikibase recent changes feature disabled?
	 *
	 * @since 0.4
	 *
	 * @return bool
	 */
	protected function isDisabled() {
		$rcSetting = WikibaseClient::getDefaultInstance()->getSettings()
			->getSetting( 'showExternalRecentChanges' );

		// sanity check for the setting
		if ( !is_bool( $rcSetting ) ) {
			$rcSetting = false;
		}

		return !$rcSetting;
	}

	/**
	 * Is hidewikibase filter selected?
	 *
	 * @since 0.4
	 *
	 * @return bool
	 */
	protected function hideWikibase() {
		// backwards compat
		$hideWikidata = isset( $this->opts['hidewikidata'] ) && $this->opts['hidewikidata'] === true;

		$hideWikibase = isset( $this->opts['hidewikibase'] ) && $this->opts['hidewikibase'] === true;

		return ( $hideWikibase || $hideWikidata );
	}

	/**
	 * Is the enhanced changes format used?
	 *
	 * @note this is temporary and we will support enhanced changes in the near future
	 *
	 * @since 0.4
	 *
	 * @return bool
	 */
	protected function isEnhancedChanges() {
		// @todo evil globals, though the recent changes and watchlist query hooks
		// so nor provide access to context
		global $wgRequest, $wgUser;
		return $wgRequest->getBool( 'enhanced', $wgUser->getOption( 'usenewrc' ) );
	}

	/**
	 * Do we show wikibase edits in recent changes?
	 *
	 * @since 0.4
	 *
	 * @return bool
	 */
	public function showWikibaseEdits() {
		if ( $this->isDisabled() || $this->hideWikibase() || $this->isEnhancedChanges() ) {
			return false;
		}
		return true;
	}

}

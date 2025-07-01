<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use MediaWiki\MediaWikiServices;

/**
 * @license GPL-2.0-or-later
 */
trait CirrusSearchEnabledTrait {

	public static function isCirrusSearchEnabled(): bool {
		global $wgSearchType, $wgWBCSUseCirrus;

		$isWikibaseCirrusSearchEnabled = MediaWikiServices::getInstance()
			->getExtensionRegistry()
			->isLoaded( 'WikibaseCirrusSearch' );
		$isCirrusSearchEnabled = $wgSearchType === 'CirrusSearch' || $wgWBCSUseCirrus;

		return $isCirrusSearchEnabled && $isWikibaseCirrusSearchEnabled;
	}
}

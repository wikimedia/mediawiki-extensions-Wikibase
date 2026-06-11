<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use MediaWiki\MediaWikiServices;

/**
 * @license GPL-2.0-or-later
 */
trait CirrusSearchEnabledTrait {

	public static function isCirrusSearchEnabled(): bool {
		global $wgWBCSUseCirrus;

		// $wgWBCSUseCirrus is sufficient here: InLabelSearch and EntitySearchHelper are
		// provided directly by WikibaseCirrusSearch regardless of the MW search engine.
		return $wgWBCSUseCirrus && MediaWikiServices::getInstance()
			->getExtensionRegistry()
			->isLoaded( 'WikibaseCirrusSearch' );
	}
}

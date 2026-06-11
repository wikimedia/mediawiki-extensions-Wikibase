<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use MediaWiki\MediaWikiServices;

/**
 * @license GPL-2.0-or-later
 */
trait CirrusSearchEnabledTrait {

	public static function isCirrusSearchEnabled(): bool {
		global $wgSearchType, $wgWBCSUseCirrus;

		// $wgSearchType === 'CirrusSearch' is required here in addition to $wgWBCSUseCirrus:
		// haswbstatement: query syntax (used by CirrusSearchFacetedSearchEngine) is only
		// registered when CirrusSearch is the active MW search engine.
		return $wgWBCSUseCirrus
			&& $wgSearchType === 'CirrusSearch'
			&& MediaWikiServices::getInstance()
				->getExtensionRegistry()
				->isLoaded( 'WikibaseCirrusSearch' );
	}

}

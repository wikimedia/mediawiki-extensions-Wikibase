<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use MediaWiki\Registration\ExtensionRegistry;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\SearchNotAvailable;

/**
 * @license GPL-2.0-or-later
 */
class SearchItemsResolver {

	public function __construct(
		private readonly FacetedItemSearch $searchUseCase,
		private readonly ExtensionRegistry $extensionRegistry,
	) {
	}

	/**
	 * @throws SearchNotAvailable
	 */
	public function resolve( array $query ): array {

		if ( !$this->isSearchEnabled() ) {
			throw new SearchNotAvailable();
		}

		return $this->searchUseCase->execute( new FacetedItemSearchRequest( $query ) )->results;
	}

	private function isSearchEnabled(): bool {
		global $wgSearchType, $wgWBCSUseCirrus;

		$isWikibaseCirrusSearchEnabled = $this->extensionRegistry->isLoaded( 'WikibaseCirrusSearch' );
		$isCirrusSearchEnabled = $wgSearchType === 'CirrusSearch' || $wgWBCSUseCirrus;

		return $isCirrusSearchEnabled && $isWikibaseCirrusSearchEnabled;
	}
}

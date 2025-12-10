<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use LogicException;
use MediaWiki\Registration\ExtensionRegistry;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseErrorType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\InvalidSearchQuery;
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
	 * @throws InvalidSearchQuery
	 */
	public function resolve( array $query ): array {
		if ( !$this->isSearchEnabled() ) {
			throw new SearchNotAvailable();
		}

		try {
			return $this->searchUseCase->execute( new FacetedItemSearchRequest( $query ) )->results;
		} catch ( UseCaseError $e ) {
			throw match ( $e->type ) {
				UseCaseErrorType::INVALID_SEARCH_QUERY => new InvalidSearchQuery( $e->getMessage() ),
				default => new LogicException( "Unexpected error type: '{$e->type->name}'" ),
			};
		}
	}

	private function isSearchEnabled(): bool {
		global $wgSearchType, $wgWBCSUseCirrus;

		$isWikibaseCirrusSearchEnabled = $this->extensionRegistry->isLoaded( 'WikibaseCirrusSearch' );
		$isCirrusSearchEnabled = $wgSearchType === 'CirrusSearch' || $wgWBCSUseCirrus;

		return $isCirrusSearchEnabled && $isWikibaseCirrusSearchEnabled;
	}
}

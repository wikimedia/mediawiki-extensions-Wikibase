<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use LogicException;
use MediaWiki\Registration\ExtensionRegistry;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseErrorType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\InvalidSearchCursor;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\InvalidSearchLimit;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\InvalidSearchQuery;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\SearchNotAvailable;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\PaginationCursorCodec;

/**
 * @license GPL-2.0-or-later
 */
class SearchItemsResolver {
	use PaginationCursorCodec;

	public function __construct(
		private readonly FacetedItemSearch $searchUseCase,
		private readonly ExtensionRegistry $extensionRegistry,
	) {
	}

	/**
	 * @throws SearchNotAvailable
	 * @throws InvalidSearchQuery
	 * @throws InvalidSearchLimit
	 * @throws InvalidSearchCursor
	 */
	public function resolve( array $query, int $limit, ?string $cursor ): array {
		if ( !$this->isSearchEnabled() ) {
			throw new SearchNotAvailable();
		}

		$offset = $cursor ? $this->decodeOffsetFromCursor( $cursor ) : 0;

		try {
			return $this->searchUseCase->execute( new FacetedItemSearchRequest( $query, $limit, $offset ) )->results;
		} catch ( UseCaseError $e ) {
			throw match ( $e->type ) {
				UseCaseErrorType::INVALID_SEARCH_QUERY => new InvalidSearchQuery( $e->getMessage() ),
				UseCaseErrorType::INVALID_SEARCH_LIMIT => new InvalidSearchLimit(),
				UseCaseErrorType::INVALID_SEARCH_OFFSET => new InvalidSearchCursor(),
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

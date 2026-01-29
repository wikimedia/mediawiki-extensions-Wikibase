<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use LogicException;
use MediaWiki\Registration\ExtensionRegistry;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseErrorType;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResultSet;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;
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
	 * @throws GraphQLError
	 */
	public function resolve( array $query, int $limit, ?string $cursor ): array {
		if ( !$this->isSearchEnabled() ) {
			throw GraphQLError::searchNotAvailable();
		}

		$offset = $cursor ? $this->decodeOffsetFromCursor( $cursor ) : 0;

		try {
			$searchResults = $this->searchUseCase->execute( new FacetedItemSearchRequest( $query, $limit, $offset ) )->results;
		} catch ( UseCaseError $e ) {
			throw match ( $e->type ) {
				UseCaseErrorType::INVALID_SEARCH_QUERY => GraphQLError::invalidSearchQuery( $e->getMessage() ),
				UseCaseErrorType::INVALID_SEARCH_LIMIT => GraphQLError::invalidSearchLimit(),
				UseCaseErrorType::INVALID_SEARCH_OFFSET => GraphQLError::invalidSearchCursor(),
				default => new LogicException( "Unexpected error type: '{$e->type->name}'" ),
			};
		}

		return [
			'edges' => $this->resolveEdges( $searchResults->results, $offset ),
			'pageInfo' => $this->resolvePageInfo( $searchResults, $offset ),
		];
	}

	private function isSearchEnabled(): bool {
		global $wgSearchType, $wgWBCSUseCirrus;

		$isWikibaseCirrusSearchEnabled = $this->extensionRegistry->isLoaded( 'WikibaseCirrusSearch' );
		$isCirrusSearchEnabled = $wgSearchType === 'CirrusSearch' || $wgWBCSUseCirrus;

		return $isCirrusSearchEnabled && $isWikibaseCirrusSearchEnabled;
	}

	private function resolveEdges( array $searchResults, int $offset ): array {
		return array_map(
			fn( ItemSearchResult $result, int $key ) => [
				'node' => $result,
				'cursor' => $this->encodeOffsetAsCursor( $offset + $key + 1 ),
			],
			$searchResults,
			array_keys( $searchResults ),
		);
	}

	private function resolvePageInfo( ItemSearchResultSet $searchResults, int $offset ): array {
		$numResults = count( $searchResults->results );
		$hasResults = $numResults > 0;
		$endOffset = $numResults + $offset;

		return [
			'endCursor' => $hasResults ? $this->encodeOffsetAsCursor( $endOffset ) : null,
			'hasPreviousPage' => $offset > 0,
			'hasNextPage' => $searchResults->totalResults > $endOffset,
			'startCursor' => $hasResults ? $this->encodeOffsetAsCursor( $offset + 1 ) : null,
		];
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use ISearchResultSet;
use MediaWiki\Status\Status;
use SearchEngine;
use SearchResult;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiSearchEngine implements ItemSearchEngine {
	private const RESULTS_LIMIT = 5;

	private SearchEngine $searchEngine;
	private EntityNamespaceLookup $namespaceLookup;

	public function __construct( SearchEngine $searchEngine, EntityNamespaceLookup $namespaceLookup ) {
		$this->searchEngine = $searchEngine;
		$this->namespaceLookup = $namespaceLookup;
	}

	public function searchItemByLabel( string $searchTerm, string $languageCode ): ItemSearchResults {
		$this->searchEngine->setNamespaces( [ $this->getItemNamespace() ] );
		$this->searchEngine->setLimitOffset( self::RESULTS_LIMIT );

		return $this->convertSearchResults(
			$this->doSearch( $searchTerm )
		);
	}

	/**
	 * @param string $searchTerm
	 *
	 * @return SearchResult[]
	 */
	private function doSearch( string $searchTerm ): array {
		$result = $this->searchEngine->searchText( $searchTerm );

		if ( $result instanceof ISearchResultSet ) {
			return $result->extractResults();
		} elseif ( $result instanceof Status && $result->isOK() ) {
			return $result->getValue()->extractResults();
		}

		return [];
	}

	/**
	 * @param SearchResult[] $results
	 *
	 * @return ItemSearchResults
	 */
	private function convertSearchResults( array $results ): ItemSearchResults {
		return new ItemSearchResults(
			...array_map(
				function ( SearchResult $result ) {
					return new ItemSearchResult(
						new ItemId( $result->getTitle()->getText() ),
						// @phan-suppress-next-line PhanUndeclaredMethod - phan does not know about WikibaseCirrusSearch
						$result->getLabelData()['value'],
						// @phan-suppress-next-line PhanUndeclaredMethod - phan does not know about WikibaseCirrusSearch
						$result->getDescriptionData()['value']
					);
				},
				$results
			)
		);
	}

	private function getItemNamespace(): int {
		return $this->namespaceLookup->getEntityNamespace( Item::ENTITY_TYPE );
	}
}

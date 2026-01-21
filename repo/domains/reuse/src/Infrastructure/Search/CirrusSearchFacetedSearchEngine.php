<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\Search;

use ISearchResultSet;
use SearchEngineFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Domains\Reuse\Domain\Model\AndOperation;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResultSet;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValueFilter;
use Wikibase\Repo\Domains\Reuse\Domain\Services\FacetedItemSearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class CirrusSearchFacetedSearchEngine implements FacetedItemSearchEngine {

	public function __construct(
		private readonly SearchEngineFactory $searchEngineFactory,
		private readonly EntityNamespaceLookup $entityNamespaceLookup,

	) {
	}

	public function search( AndOperation|PropertyValueFilter $query, int $limit, int $offset ): ItemSearchResultSet {
		$searchEngine = $this->searchEngineFactory->create();
		$searchEngine->setNamespaces(
			[ $this->entityNamespaceLookup->getEntityNamespace( Item::ENTITY_TYPE ) ]
		);
		$searchEngine->setLimitOffset( $limit, $offset );
		$searchQuery = $this->criteriaToSearchQuery( $query );
		$resultSet = $searchEngine->searchText( $searchQuery );
		if ( !$resultSet || !( $resultSet->getValue() instanceof ISearchResultSet ) ) {
			return new ItemSearchResultSet( [], 0 );
		}
		$totalResults = $resultSet->getValue()->getTotalHits();

		return new ItemSearchResultSet(
			array_map(
				fn( $result ) => new ItemSearchResult( new ItemId( $result->getTitle()->getText() ) ),
				$resultSet->getValue()->extractResults()
			),
			$totalResults
		);
	}

	private function criteriaToSearchQuery( AndOperation|PropertyValueFilter $criteria ): string {
		if ( $criteria instanceof PropertyValueFilter ) {
			return $criteria->value === null
				? "haswbstatement:{$criteria->propertyId}"
				: "haswbstatement:{$criteria->propertyId}={$criteria->value}";
		}
		return implode(
			' ',
			array_map( $this->criteriaToSearchQuery( ... ), $criteria->filters )
		);
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Domains\Reuse\Domain\Model\AndOperation;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResultSet;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValueFilter;
use Wikibase\Repo\Domains\Reuse\Domain\Services\FacetedItemSearchEngine;

/**
 *  An in-memory implementation of the FacetedItemSearchEngine used for testing.
 *
 *  This search engine stores a set of Items in memory and
 *  performs PropertyValueFilter and AndNode queries matching against them.
 *  This simulates search behavior without relying on any search services or extensions.
 *
 *  Provided for use in tests only.
 *
 * @license GPL-2.0-or-later
 */
class InMemoryFacetedItemSearchEngine implements FacetedItemSearchEngine {

	/**
	 * @var Item[]
	 */
	private array $items = [];

	public function search( AndOperation|PropertyValueFilter $query, int $limit, int $offset ): ItemSearchResultSet {
		$result = [];
		foreach ( $this->items as $item ) {
			if ( $this->matchesQuery( $query, $item ) ) {
				$result[] = new ItemSearchResult( $item->getId() );
			}
		}
		return new ItemSearchResultSet( array_slice( $result, $offset, $limit ), count( $result ) );
	}

	public function addItem( Item $item ): void {
		if ( $item->getId() === null ) {
			throw new InvalidArgumentException( 'The entity needs to have an ID' );
		}

		$this->items[] = $item;
	}

	private function matchesQuery( AndOperation|PropertyValueFilter $query, Item $item ): bool {
		if ( $query instanceof PropertyValueFilter ) {
			return $this->matchesPropertyValueFilter( $query, $item );
		}

		foreach ( $query->filters as $filter ) {
			if ( !$this->matchesQuery( $filter, $item ) ) {
				return false;
			}
		}

		return true;
	}

	private function matchesPropertyValueFilter( PropertyValueFilter $filter, Item $item ): bool {
		$filterValue = $filter->value;
		$statements = $item->getStatements()->getByPropertyId( $filter->propertyId );

		if ( $filterValue === null ) {
			return $statements->count() > 0;
		}

		foreach ( $statements as $statement ) {
			$snak = $statement->getMainSnak();

			if ( !( $snak instanceof PropertyValueSnak ) ) {
				continue;
			}

			$dataValue = $snak->getDataValue();
			if ( $this->dataValueMatchesFilterValue( $dataValue, $filterValue ) ) {
				return true;
			}
		}
		return false;
	}

	private function dataValueMatchesFilterValue( DataValue $dataValue, string $filterValue ): bool {
		return ( $dataValue instanceof StringValue && $dataValue->getValue() === $filterValue ) ||
			( $dataValue instanceof EntityIdValue && $dataValue->getEntityId()->getSerialization() === $filterValue );
	}
}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch;

use LogicException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseErrorType;
use Wikibase\Repo\Domains\Reuse\Domain\Model\AndOperation;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValueFilter;

/**
 * @license GPL-2.0-or-later
 */
class FacetedItemSearchValidator {
	private AndOperation|PropertyValueFilter|null $query = null;

	public function __construct(
		private readonly PropertyDataTypeLookup $dataTypeLookup,
		private readonly array $valueTypesMap,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( FacetedItemSearchRequest $req ): void {
		$this->validateLimit( $req->limit );
		$this->validateOffset( $req->offset );
		$this->query = $this->constructQuery( $req->query );
	}

	public function getValidatedQuery(): AndOperation|PropertyValueFilter {
		if ( $this->query === null ) {
			throw new LogicException( 'Must not call getValidatedQuery() before validate()' );
		}

		return $this->query;
	}

	/**
	 * @throws UseCaseError
	 */
	private function constructQuery( array $filter ): AndOperation|PropertyValueFilter {
		if ( !isset( $filter['property'] ) && !isset( $filter['and'] ) ) {
			$this->throwInvalidQuery( "Query filters must contain either an 'and' or a 'property' field" );
		}
		if ( isset( $filter['property'] ) && isset( $filter['and'] ) ) {
			$this->throwInvalidQuery( "Filters must not contain both an 'and' and a 'property' field" );
		}
		if ( isset( $filter['and'] ) && count( $filter['and'] ) < 2 ) {
			$this->throwInvalidQuery( "'and' fields must contain at least two elements" );
		}

		if ( isset( $filter['property'] ) ) {
			return $this->constructPropertyValueFilter( $filter );
		}

		return new AndOperation(
			array_map(
				$this->constructQuery( ... ),
				$filter['and']
			)
		);
	}

	private function isSupportedDataType( string $dataType ): bool {
		return $dataType === 'wikibase-item' || $this->valueTypesMap[$dataType] === 'string';
	}

	/**
	 * @throws UseCaseError
	 */
	private function throwInvalidQuery( string $message ): never {
		throw new UseCaseError( UseCaseErrorType::INVALID_SEARCH_QUERY, $message );
	}

	/**
	 * @throws UseCaseError
	 */
	public function constructPropertyValueFilter( array $filter ): PropertyValueFilter {
		try {
			$isSupportedDataType = $this->isSupportedDataType(
				$this->dataTypeLookup
					->getDataTypeIdForProperty( new NumericPropertyId( $filter['property'] ) )
			);
		} catch ( PropertyDataTypeLookupException ) {
			$isSupportedDataType = false;
		}

		if ( !$isSupportedDataType ) {
			$this->throwInvalidQuery( "Data type of Property '{$filter['property']}' is not supported" );
		}

		return new PropertyValueFilter(
			new NumericPropertyId( $filter['property'] ),
			$filter['value'] ?? null
		);
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateLimit( int $limit ): void {
		if ( $limit < 1 || $limit > FacetedItemSearchRequest::MAX_LIMIT ) {
			throw new UseCaseError( UseCaseErrorType::INVALID_SEARCH_LIMIT, "Invalid limit: $limit" );
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateOffset( int $offset ): void {
		if ( $offset < 0 || $offset > FacetedItemSearchRequest::MAX_OFFSET ) {
			throw new UseCaseError( UseCaseErrorType::INVALID_SEARCH_OFFSET, "Invalid offset: $offset" );
		}
	}
}

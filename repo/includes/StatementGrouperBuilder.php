<?php

namespace Wikibase\Repo;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\Filter\DataTypeStatementFilter;
use Wikibase\DataModel\Services\Statement\Filter\NullStatementFilter;
use Wikibase\DataModel\Services\Statement\Filter\PropertySetStatementFilter;
use Wikibase\DataModel\Services\Statement\Grouper\FilteringStatementGrouper;
use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementFilter;

/**
 * Factory for a StatementGrouper. The grouper is instantiated based on a specification array that
 * has the following form:
 *
 * [
 *     'item' => [
 *         'statements' => null,
 *         'example' => [
 *             'type' => 'propertySet',
 *             'propertyIds' => [ 'P1' ],
 *         ],
 *         'identifiers' => [
 *             'type' => 'dataType',
 *             'dataTypes' => [ 'external-id' ],
 *         ],
 *     ],
 *     'property' => [
 *     ],
 * ],
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class StatementGrouperBuilder {

	/**
	 * @var array[]
	 */
	private $specifications;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var StatementGuidParser
	 */
	private $statementGuidParser;

	/**
	 * @param array[] $specifications See the class level documentation for details.
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param StatementGuidParser $statementGuidParser
	 */
	public function __construct(
		array $specifications,
		PropertyDataTypeLookup $dataTypeLookup,
		StatementGuidParser $statementGuidParser
	) {
		$this->specifications = $specifications;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->statementGuidParser = $statementGuidParser;
	}

	/**
	 * @throws InvalidArgumentException
	 * @return StatementGrouper
	 */
	public function getStatementGrouper() {
		$groupers = [];

		foreach ( $this->specifications as $entityType => $filterSpecs ) {
			$groupers[$entityType] = $filterSpecs === null
				? new NullStatementGrouper()
				: $this->newFilteringStatementGrouper( $filterSpecs );
		}

		return new DispatchingEntityTypeStatementGrouper( $this->statementGuidParser, $groupers );
	}

	/**
	 * @param array[] $filterSpecs
	 *
	 * @throws InvalidArgumentException
	 * @return FilteringStatementGrouper
	 */
	private function newFilteringStatementGrouper( array $filterSpecs ) {
		$filters = [];

		foreach ( $filterSpecs as $groupIdentifier => $spec ) {
			$filters[$groupIdentifier] = $spec === null
				? null
				: $this->newStatementFilter( $spec );
		}

		return new FilteringStatementGrouper( $filters );
	}

	/**
	 * @param array $spec
	 *
	 * @throws InvalidArgumentException
	 * @return StatementFilter
	 */
	private function newStatementFilter( array $spec ) {
		$this->requireField( $spec, 'type' );

		switch ( $spec['type'] ) {
			case null:
				return new NullStatementFilter();
			case DataTypeStatementFilter::FILTER_TYPE:
				$this->requireField( $spec, 'dataTypes' );
				return new DataTypeStatementFilter( $this->dataTypeLookup, $spec['dataTypes'] );
			case PropertySetStatementFilter::FILTER_TYPE:
				$this->requireField( $spec, 'propertyIds' );
				return new PropertySetStatementFilter( $spec['propertyIds'] );
			// Be aware that this switch statement is a possible violation of the open-closed
			// principle. When the number of filters grows, please try to extract this in a way that
			// it can be injected.
		}

		throw new InvalidArgumentException( 'Unknown filter type: ' . $spec['type'] );
	}

	/**
	 * @param array $spec
	 * @param string $field
	 *
	 * @throws InvalidArgumentException
	 */
	private function requireField( array $spec, $field ) {
		if ( !array_key_exists( $field, $spec ) ) {
			throw new InvalidArgumentException(
				"Statement group configuration misses required field '$field'"
			);
		}
	}

}

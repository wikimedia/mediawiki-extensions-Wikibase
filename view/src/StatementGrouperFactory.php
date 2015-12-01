<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\Filter\DataTypeStatementFilter;
use Wikibase\DataModel\Services\Statement\Filter\PropertySetStatementFilter;
use Wikibase\DataModel\Services\Statement\Grouper\FilteringStatementGrouper;
use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Statement\StatementFilter;

/**
 * Factory for StatementGroupers for different entity types. The groupers are instantiated based
 * on a specification array that has the following form:
 *
 * @code
	array(
		'item' => array(
			'other' => null,
			'identifiers' => array(
					'type' => 'data-type',
					'data-types' => array( 'identifier' ),
			),
		),
		'property' => array(
			'other' => null,
			'constraints' => array(
				'type' => 'property-list',
				'properties' => array( 'P111' ),
			),
		),
	)
 * @endcode
 *
 * On the top level, there are the entity types for which a groupign is defiend. On the second level,
 * the seconds are defined. On the third level, there are arrays that define the filter to use for
 * each section (or null, for the default section). The "type" field in the filter definition
 * determines the class to use, other fields depend on the type.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class StatementGrouperFactory {

	/**
	 * @var array[]
	 */
	private $statementGroupSpecs;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @param array[] $statementGroupSpecs The statement group specifications.
	 *        See class level documentation for details.
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 */
	public function __config( array $statementGroupSpecs, PropertyDataTypeLookup $dataTypeLookup ) {
		$this->statementGroupSpecs = $statementGroupSpecs;
		$this->dataTypeLookup = $dataTypeLookup;
	}

	/**
	 * @param string $entityType
	 *
	 * @return StatementGrouper
	 */
	public function getStatementGrouper( $entityType ) {
		if ( !isset( $this->statementGroupSpecs[$entityType] ) ) {
			return new NullStatementGrouper();
		}

		$filters = $this->makeFilters( $this->statementGroupSpecs[$entityType] );
		return new FilteringStatementGrouper( $filters );
	}

	/**
	 * @param array[] $sectionSpecs
	 *
	 * @return StatementFilter[] Filters by section name. Null is allowed as a value.
	 */
	private function makeFilters( array $sectionSpecs ) {
		$filters = array();

		foreach ( $sectionSpecs as $section => $filterSpec ) {
			$filters[$section] = $filterSpec === null ? null : $this->newFiterFromSpec( $filterSpec );
		}

		return $filters;
	}

	/**
	 * @param array $filterSpec
	 *
	 * @return StatementFilter
	 */
	private function newFiterFromSpec( array $filterSpec ) {
		$type = $this->requireField( $filterSpec, 'type' );

		switch ( $type ) {
			case 'data-type':
				$dataTypes = $this->requireField( $filterSpec, 'data-types' );
				return new DataTypeStatementFilter( $this->dataTypeLookup, $dataTypes );

			case 'property':
				$properties = $this->requireField( $filterSpec, 'properties' );
				return new PropertySetStatementFilter( $properties );
		}

		throw new InvalidArgumentException( 'Unknown filter type: ' . $filterSpec['type'] );
	}

	/**
	 * @param array $data
	 * @param string $field
	 *
	 * @return mixed
	 */
	private function requireField( array $data, $field ) {
		if ( !isset( $data[$field] ) ) {
			throw new InvalidArgumentException( "missing required field `$field`" );
		}

		return $data[$field];
	}
}

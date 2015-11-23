<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Statement\Filter\StatementFilter;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class FilteringStatementGrouper implements StatementGrouper {

	/**
	 * @var StatementList[]
	 */
	private $groups = array();

	/**
	 * @var StatementFilter[] An associative array, mapping statement group identifiers to filters.
	 */
	private $filters = array();

	/**
	 * @var string
	 */
	private $defaultGroupIdentifier = 'statements';

	/**
	 * @see \Wikibase\DataModel\Services\Statement\Filter\StatementFilter
	 *
	 * @param array $filters An associative array, mapping statement group identifiers to either
	 *  StatementFilter objects, or to null for the default group.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $filters ) {
		$this->setFilters( $filters );
	}

	/**
	 * @param array $filters
	 *
	 * @throws InvalidArgumentException
	 */
	private function setFilters( array $filters ) {
		foreach ( $filters as $key => $filter ) {
			$this->initializeGroup( $key );

			if ( $filter === null ) {
				$this->defaultGroupIdentifier = $key;
			} elseif ( $filter instanceof StatementFilter ) {
				$this->filters[$key] = $filter;
			} else {
				throw new InvalidArgumentException( '$filter must be a StatementFilter or null' );
			}
		}

		$this->initializeGroup( $this->defaultGroupIdentifier );
	}

	private function initializeGroup( $key ) {
		$this->groups[$key] = new StatementList();
	}

	/**
	 * @param StatementList $statements
	 *
	 * @return StatementList[] An associative array, mapping statement group identifiers to
	 *  StatementList objects. All identifiers given in the constructor are guaranteed to be in the
	 *  result.
	 */
	public function groupStatements( StatementList $statements ) {
		foreach ( $statements->toArray() as $statement ) {
			$key = $this->getKey( $statement );
			$this->groups[$key]->addStatement( $statement );
		}

		return $this->groups;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return string Statement group identifier
	 */
	private function getKey( Statement $statement ) {
		foreach ( $this->filters as $key => $filter ) {
			if ( $filter->isMatch( $statement ) ) {
				return $key;
			}
		}

		return $this->defaultGroupIdentifier;
	}

}

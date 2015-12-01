<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementFilter;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class FilteringStatementGrouper implements StatementGrouper {

	/**
	 * @var StatementFilter[] An associative array, mapping statement group identifiers to filters.
	 */
	private $filters = array();

	/**
	 * @var string[] Array of group keys
	 */
	private $groupKeys = array();

	/**
	 * @var string
	 */
	private $defaultGroupIdentifier = null;

	/**
	 *
	 * @see StatementFilter
	 *
	 * @param array $filters
	 *        	An associative array, mapping statement group identifiers to either
	 *        	StatementFilter objects, or to null for the default group.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $filters ) {

		foreach ( $filters as $key => $filter ) {
			$this->addFilter( $filter, $key );
			$this->groupKeys[] = $key;
		}

		if ( $this->defaultGroupIdentifier === null ) {
			$this->defaultGroupIdentifier = 'statements';
			$this->groupKeys[] = $this->defaultGroupIdentifier;
		}
	}

	/**
	 * @param StatementFilter|null $filter
	 * @param string $key
	 */
	private function addFilter( StatementFilter $filter = null, $key ) {
		if ( $filter === null ) {
			if ( $this->defaultGroupIdentifier !== null ) {
				throw new InvalidArgumentException( 'You must only define one default group' );
			}
			$this->defaultGroupIdentifier = $key;
		} elseif ( $filter instanceof StatementFilter ) {
			$this->filters[$key] = $filter;
		}
	}

	/**
	 * @param StatementList $statements
	 *
	 * @return StatementList[] An associative array, mapping statement group identifiers to
	 *  StatementList objects. All identifiers given in the constructor are guaranteed to be in the
	 *  result.
	 */
	public function groupStatements( StatementList $statements ) {
		$groups = $this->getEmptyGroups();

		foreach ( $statements->toArray() as $statement ) {
			$key = $this->getKey( $statement );
			$groups[$key]->addStatement( $statement );
		}

		return $groups;
	}

	private function getEmptyGroups() {
		$groups = array();

		foreach ( $this->groupKeys as $key ) {
			$groups[$key] = new StatementList();
		}
		return $groups;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return string Statement group identifier
	 */
	private function getKey( Statement $statement ) {
		foreach ( $this->filters as $key => $filter ) {
			if ( $filter->statementMatches( $statement ) ) {
				return $key;
			}
		}

		return $this->defaultGroupIdentifier;
	}

}

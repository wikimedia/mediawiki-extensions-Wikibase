<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

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
	 * @param array $filters An associative array, mapping statement group identifiers to either
	 *  filters, or to null for the default group.
	 */
	public function __construct( array $filters ) {
		$this->setFilters( $filters );
	}

	private function setFilters( array $filters ) {
		foreach ( $filters as $key => $filter ) {
			$this->groups[$key] = new StatementList();

			if ( $filter === null ) {
				$this->defaultGroupIdentifier = $key;
			} else {
				$this->filters[$key] = $filter;
			}
		}

		$this->groups[$this->defaultGroupIdentifier] = new StatementList();
	}

	/**
	 * @param StatementList $statements
	 *
	 * @return StatementList[] An associative array, mapping statement group identifiers to
	 *  StatementList objects.
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

<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementFilter;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 3.2
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FilteringStatementGrouper implements StatementGrouper {

	/**
	 * @var StatementFilter[] An associative array, mapping statement group identifiers to filters.
	 */
	private $filters = [];

	/**
	 * @var string[]
	 */
	private $groupIdentifiers = [];

	/**
	 * @var string
	 */
	private $defaultGroupIdentifier = null;

	/**
	 * @see StatementFilter
	 *
	 * @param array $filters An associative array, mapping statement group identifiers to either
	 *  StatementFilter objects, or to null for the default group.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $filters ) {
		foreach ( $filters as $key => $filter ) {
			if ( $filter instanceof StatementFilter ) {
				$this->filters[$key] = $filter;
			} elseif ( $filter === null ) {
				$this->setDefaultGroupIdentifier( $key );
			} else {
				throw new InvalidArgumentException( '$filter must be a StatementFilter or null' );
			}

			$this->setGroupIdentifier( $key );
		}

		$this->initializeDefaultGroup();
	}

	/**
	 * @param string $key
	 */
	private function setGroupIdentifier( $key ) {
		$this->groupIdentifiers[$key] = $key;
	}

	/**
	 * @param string $key
	 *
	 * @throws InvalidArgumentException
	 */
	private function setDefaultGroupIdentifier( $key ) {
		if ( $this->defaultGroupIdentifier !== null ) {
			throw new InvalidArgumentException( 'You must only define one default group' );
		}

		$this->defaultGroupIdentifier = $key;
	}

	private function initializeDefaultGroup() {
		if ( $this->defaultGroupIdentifier === null ) {
			$this->defaultGroupIdentifier = 'statements';
			$this->setGroupIdentifier( $this->defaultGroupIdentifier );
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

	/**
	 * @return StatementList[]
	 */
	private function getEmptyGroups() {
		$groups = [];

		foreach ( $this->groupIdentifiers as $key ) {
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

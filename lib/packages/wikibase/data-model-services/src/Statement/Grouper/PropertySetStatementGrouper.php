<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class PropertySetStatementGrouper implements StatementGrouper {

	/**
	 * @var StatementList[]
	 */
	private $groups = array();

	/**
	 * @var string[] An associative array, mapping property id serializations to statement group
	 *  identifiers.
	 */
	private $groupIdentifiers = array();

	/**
	 * @var string
	 */
	private $defaultGroupIdentifier = 'statements';

	/**
	 * @param array $groupDefinitions An associative array, mapping statement group identifiers to
	 *  either arrays of property id serializations, or to null for the default group.
	 */
	public function __construct( array $groupDefinitions = array() ) {
		$this->setGroupDefinitions( $groupDefinitions );
	}

	private function setGroupDefinitions( array $groupDefinitions ) {
		foreach ( $groupDefinitions as $key => $propertyIds ) {
			$this->groups[$key] = new StatementList();

			if ( $propertyIds === null ) {
				$this->defaultGroupIdentifier = $key;
			} else {
				foreach ( (array)$propertyIds as $id ) {
					$this->groupIdentifiers[$id] = $key;
				}
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
		$id = $statement->getPropertyId()->getSerialization();
		return isset( $this->groupIdentifiers[$id] )
			? $this->groupIdentifiers[$id]
			: $this->defaultGroupIdentifier;
	}

}

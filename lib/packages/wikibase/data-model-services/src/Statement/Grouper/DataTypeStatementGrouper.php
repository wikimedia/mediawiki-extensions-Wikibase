<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DataTypeStatementGrouper implements StatementGrouper {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var StatementList[]
	 */
	private $groups = array();

	/**
	 * @var string[] An associative array, mapping data type identifiers to statement group
	 *  identifiers.
	 */
	private $groupIdentifiers = array();

	/**
	 * @var string
	 */
	private $defaultGroupIdentifier = 'statements';

	/**
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param array $groupDefinitions An associative array, mapping statement group identifiers to
	 *  either arrays of data type identifiers, or to null for the default group.
	 */
	public function __construct(
		PropertyDataTypeLookup $dataTypeLookup,
		array $groupDefinitions = array()
	) {
		$this->dataTypeLookup = $dataTypeLookup;
		$this->setGroupDefinitions( $groupDefinitions );
	}

	private function setGroupDefinitions( array $groupDefinitions ) {
		foreach ( $groupDefinitions as $key => $dataTypes ) {
			$this->groups[$key] = new StatementList();

			if ( $dataTypes === null ) {
				$this->defaultGroupIdentifier = $key;
			} else {
				foreach ( (array)$dataTypes as $dataType ) {
					$this->groupIdentifiers[$dataType] = $key;
				}
			}
		}
	}

	/**
	 * @param StatementList $statements
	 *
	 * @return StatementList[] An associative array, mapping statement group identifiers to
	 *  StatementList objects.
	 */
	public function groupStatements( StatementList $statements ) {
		foreach ( $statements->toArray() as $statement ) {
			$key = $this->getGroupIdentifier( $statement );
			$this->groups[$key]->addStatement( $statement );
		}

		return $this->groups;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return string Statement group identifier
	 */
	private function getGroupIdentifier( Statement $statement ) {
		$id = $statement->getPropertyId();

		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $id );
		} catch ( PropertyDataTypeLookupException $ex ) {
			return $this->defaultGroupIdentifier;
		}

		return isset( $this->groupIdentifiers[$dataType] )
			? $this->groupIdentifiers[$dataType]
			: $this->defaultGroupIdentifier;
	}

}

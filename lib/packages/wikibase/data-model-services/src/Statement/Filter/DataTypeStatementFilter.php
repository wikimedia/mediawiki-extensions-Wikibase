<?php

namespace Wikibase\DataModel\Services\Statement\Filter;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\Statement;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DataTypeStatementFilter implements StatementFilter {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var string[]
	 */
	private $dataTypes;

	/**
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param string[] $dataTypes
	 */
	public function __construct( PropertyDataTypeLookup $dataTypeLookup, array $dataTypes ) {
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypes = array_flip( $dataTypes );
	}

	/**
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function statementMatchesFilter( Statement $statement ) {
		$id = $statement->getPropertyId();

		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $id );
		} catch ( PropertyDataTypeLookupException $ex ) {
			return false;
		}

		return array_key_exists( $dataType, $this->dataTypes );
	}

}

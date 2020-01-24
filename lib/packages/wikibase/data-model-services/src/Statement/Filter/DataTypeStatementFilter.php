<?php

namespace Wikibase\DataModel\Services\Statement\Filter;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementFilter;

/**
 * A filter that only accepts statements with specific property data types, and rejects all other
 * property data types.
 *
 * @since 3.2
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class DataTypeStatementFilter implements StatementFilter {

	/**
	 * @since 3.3
	 */
	public const FILTER_TYPE = 'dataType';

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
	 * @param string[]|string $dataTypes One or more property data type identifiers.
	 */
	public function __construct( PropertyDataTypeLookup $dataTypeLookup, $dataTypes ) {
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypes = (array)$dataTypes;
	}

	/**
	 * @see StatementFilter::statementMatches
	 *
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function statementMatches( Statement $statement ) {
		$id = $statement->getPropertyId();

		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $id );
		} catch ( PropertyDataTypeLookupException $ex ) {
			return false;
		}

		return in_array( $dataType, $this->dataTypes );
	}

}

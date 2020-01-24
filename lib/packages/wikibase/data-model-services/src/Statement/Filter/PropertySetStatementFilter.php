<?php

namespace Wikibase\DataModel\Services\Statement\Filter;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementFilter;

/**
 * A filter that only accepts statements with specific property ids, and rejects all other
 * properties.
 *
 * @since 3.2
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class PropertySetStatementFilter implements StatementFilter {

	/**
	 * @since 3.3
	 */
	public const FILTER_TYPE = 'propertySet';

	/**
	 * @var string[]
	 */
	private $propertyIds;

	/**
	 * @param string[]|string $propertyIds One or more property id serializations.
	 */
	public function __construct( $propertyIds ) {
		$this->propertyIds = (array)$propertyIds;
	}

	/**
	 * @see StatementFilter::statementMatches
	 *
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function statementMatches( Statement $statement ) {
		$id = $statement->getPropertyId()->getSerialization();
		return in_array( $id, $this->propertyIds );
	}

}

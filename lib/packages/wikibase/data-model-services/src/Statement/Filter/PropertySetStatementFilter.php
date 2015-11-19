<?php

namespace Wikibase\DataModel\Services\Statement\Filter;

use Wikibase\DataModel\Statement\Statement;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class PropertySetStatementFilter implements StatementFilter {

	/**
	 * @var string[]
	 */
	private $propertyIds;

	/**
	 * @param string[] $propertyIds
	 */
	public function __construct( array $propertyIds ) {
		$this->propertyIds = array_flip( $propertyIds );
	}

	/**
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function statementMatchesFilter( Statement $statement ) {
		$id = $statement->getPropertyId()->getSerialization();
		return array_key_exists( $id, $this->propertyIds );
	}

}

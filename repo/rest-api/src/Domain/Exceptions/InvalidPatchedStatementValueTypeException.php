<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Exceptions;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class InvalidPatchedStatementValueTypeException extends InvalidPatchedStatementException {

	private Statement $patchedStatement;
	private PropertyId $propertyId;

	public function __construct( Statement $patchedStatement, PropertyId $propertyId ) {
		parent::__construct();
		$this->patchedStatement = $patchedStatement;
		$this->propertyId = $propertyId;
	}

	public function getPatchedStatement(): Statement {
		return $this->patchedStatement;
	}

	public function getPropertyId(): PropertyId {
		return $this->propertyId;
	}

}

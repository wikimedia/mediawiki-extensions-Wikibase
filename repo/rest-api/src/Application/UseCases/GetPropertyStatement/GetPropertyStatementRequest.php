<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementRequest {

	private string $propertyId;
	private string $statementId;

	public function __construct( string $propertyId, string $statementId ) {
		$this->propertyId = $propertyId;
		$this->statementId = $statementId;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getStatementId(): string {
		return $this->statementId;
	}
}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementRequest {

	private string $propertyId;
	private array $statement;

	public function __construct( string $propertyId, array $statement ) {
		$this->propertyId = $propertyId;
		$this->statement = $statement;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getStatement(): array {
		return $this->statement;
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementRequest implements UseCaseRequest, PropertyIdRequest, StatementIdRequest {

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

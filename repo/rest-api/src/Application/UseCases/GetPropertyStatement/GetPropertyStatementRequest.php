<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementRequest extends GetStatementRequest implements PropertyIdRequest {

	private string $propertyId;

	public function __construct( string $propertyId, string $statementId ) {
		parent::__construct( $statementId );
		$this->propertyId = $propertyId;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}
}

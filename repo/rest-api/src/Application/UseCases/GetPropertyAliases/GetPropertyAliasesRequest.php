<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesRequest implements UseCaseRequest, PropertyIdRequest {
	private string $propertyId;

	public function __construct( string $propertyId ) {
		$this->propertyId = $propertyId;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}
}

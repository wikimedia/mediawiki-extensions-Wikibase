<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsRequest implements UseCaseRequest, PropertyIdRequest, PropertyIdFilterRequest {

	private string $propertyId;
	private ?string $filterPropertyId;

	public function __construct( string $propertyId, ?string $filterPropertyId = null ) {
		$this->propertyId = $propertyId;
		$this->filterPropertyId = $filterPropertyId;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getPropertyIdFilter(): ?string {
		return $this->filterPropertyId;
	}

}

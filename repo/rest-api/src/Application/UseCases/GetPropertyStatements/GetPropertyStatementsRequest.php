<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsRequest {

	private string $propertyId;
	private ?string $filterPropertyId;

	public function __construct( string $propertyId, ?string $filterPropertyId = null ) {
		$this->propertyId = $propertyId;
		$this->filterPropertyId = $filterPropertyId;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getFilterPropertyId(): ?string {
		return $this->filterPropertyId;
	}

}

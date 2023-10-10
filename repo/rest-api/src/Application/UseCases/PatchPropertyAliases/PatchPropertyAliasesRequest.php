<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyAliasesRequest implements UseCaseRequest, PropertyIdRequest, PatchRequest {

	private string $propertyId;
	private array $patch;

	public function __construct( string $propertyId, array $patch ) {
		$this->propertyId = $propertyId;
		$this->patch = $patch;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getPatch(): array {
		return $this->patch;
	}
}

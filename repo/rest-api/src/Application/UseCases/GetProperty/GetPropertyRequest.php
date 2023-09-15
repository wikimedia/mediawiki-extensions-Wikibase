<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\Repo\RestApi\Application\UseCases\PropertyFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyRequest implements UseCaseRequest, PropertyIdRequest, PropertyFieldsRequest {

	private string $propertyId;
	private array $fields;

	public function __construct( string $propertyId, array $fields = PropertyParts::VALID_FIELDS ) {
		$this->propertyId = $propertyId;
		$this->fields = $fields;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getPropertyFields(): array {
		return $this->fields;
	}
}

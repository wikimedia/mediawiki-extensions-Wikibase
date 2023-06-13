<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyData;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyRequest {

	private string $propertyId;
	private array $fields;

	public function __construct( string $propertyId, array $fields = PropertyData::VALID_FIELDS ) {
		$this->propertyId = $propertyId;
		$this->fields = $fields;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getFields(): array {
		return $this->fields;
	}
}

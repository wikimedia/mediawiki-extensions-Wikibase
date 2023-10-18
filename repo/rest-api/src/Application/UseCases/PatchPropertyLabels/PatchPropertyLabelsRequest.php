<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyLabelsRequest {

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

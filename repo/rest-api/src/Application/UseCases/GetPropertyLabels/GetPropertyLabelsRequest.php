<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelsRequest {

	private string $propertyId;

	public function __construct( string $propertyId ) {
		$this->propertyId = $propertyId;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

}

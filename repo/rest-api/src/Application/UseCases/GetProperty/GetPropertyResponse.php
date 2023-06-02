<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyData;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyResponse {

	private PropertyData $propertyData;

	public function __construct( PropertyData $propertyData ) {
		$this->propertyData = $propertyData;
	}

	public function getPropertyData(): PropertyData {
		return $this->propertyData;
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateProperty;

use Wikibase\DataModel\Entity\Property;

/**
 * @license GPL-2.0-or-later
 */
class DeserializedCreatePropertyRequest {

	private Property $property;

	public function __construct( Property $property ) {
		$this->property = $property;
	}

	public function getProperty(): Property {
		return $this->property;
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePair {

	private Property $property;
	private Value $value;

	public function __construct( Property $property, Value $value ) {
		$this->property = $property;
		$this->value = $value;
	}

	public function getProperty(): Property {
		return $this->property;
	}

	public function getValue(): Value {
		return $this->value;
	}

}

<?php

namespace Wikibase\DataModel\Entity;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyNotFoundException extends \RuntimeException {

	protected $propertyId;

	public function __construct( PropertyId $propertyId, $message = null, \Exception $previous = null ) {
		$this->propertyId = $propertyId;

		if ( $message === null ) {
			$message = "Property not found: " . $propertyId;
		}

		parent::__construct( $message, 0, $previous );
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyId() {
		return $this->propertyId;
	}

}

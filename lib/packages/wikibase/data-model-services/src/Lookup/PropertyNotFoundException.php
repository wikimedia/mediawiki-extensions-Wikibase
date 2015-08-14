<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyNotFoundException extends EntityNotFoundException {

	public function __construct( PropertyId $propertyId, $message = null, \Exception $previous = null ) {
		if ( $message === null ) {
			$message = "Property not found: " . $propertyId;
		}

		parent::__construct( $propertyId, $message, $previous );
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyId() {
		return $this->getEntityId();
	}

}

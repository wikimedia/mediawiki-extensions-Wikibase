<?php

namespace Wikibase\DataModel\Services\Lookup;

use Exception;
use LogicException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @since 2.0
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class PropertyLookupException extends EntityLookupException {

	/**
	 * @param PropertyId $propertyId
	 * @param string|null $message
	 * @param Exception|null $previous
	 */
	public function __construct( PropertyId $propertyId, $message = null, ?Exception $previous = null ) {
		parent::__construct(
			$propertyId,
			$message ?: 'Property lookup failed for: ' . $propertyId,
			$previous
		);
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyId() {
		$propertyId = $this->getEntityId();
		if ( !( $propertyId instanceof PropertyId ) ) {
			throw new LogicException( 'expected $propertyId to be of type PropertyId' );
		}
		return $propertyId;
	}

}

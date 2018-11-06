<?php

namespace Wikibase\DataModel\Services\Lookup;

use Exception;
use RuntimeException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @since 2.0
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class PropertyDataTypeLookupException extends RuntimeException {

	/**
	 * @var PropertyId
	 */
	private $propertyId;

	/**
	 * @param PropertyId $propertyId
	 * @param string|null $message
	 * @param Exception|null $previous
	 */
	public function __construct( PropertyId $propertyId, $message = null, Exception $previous = null ) {
		$this->propertyId = $propertyId;

		parent::__construct(
			$message ?: 'Property data type lookup failed for: ' . $propertyId,
			0,
			$previous
		);
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyId() {
		return $this->propertyId;
	}

}

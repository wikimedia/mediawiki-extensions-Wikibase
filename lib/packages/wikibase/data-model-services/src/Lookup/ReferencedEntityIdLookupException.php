<?php

namespace Wikibase\DataModel\Services\Lookup;

use Exception;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @since 3.10
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ReferencedEntityIdLookupException extends RuntimeException {

	/**
	 * @var EntityId
	 */
	private $fromId;

	/**
	 * @var PropertyId
	 */
	private $propertyId;

	/**
	 * @var EntityId[]
	 */
	private $toIds;

	/**
	 * @param EntityId $fromId
	 * @param PropertyId $propertyId
	 * @param EntityId[] $toIds
	 * @param string|null $message
	 * @param Exception|null $previous
	 */
	public function __construct(
		EntityId $fromId,
		PropertyId $propertyId,
		array $toIds,
		$message = null,
		Exception $previous = null
	) {
		$this->fromId = $fromId;
		$this->propertyId = $propertyId;
		$this->toIds = $toIds;

		$targets = array_map(
			static function ( EntityId $entityId ) {
				return $entityId->getSerialization();
			},
			$toIds
		);
		$targets = implode( ', ', $targets );

		$message = $message ?: 'Referenced entity id lookup failed. Tried to find a referenced entity out of ' .
			$targets . ' linked from ' . $fromId->getSerialization() . ' via ' . $propertyId->getSerialization();

		parent::__construct( $message, 0, $previous );
	}

}

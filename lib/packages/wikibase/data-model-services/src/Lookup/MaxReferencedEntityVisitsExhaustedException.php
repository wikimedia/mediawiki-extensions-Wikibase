<?php

namespace Wikibase\DataModel\Services\Lookup;

use Exception;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @since 3.10
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class MaxReferencedEntityVisitsExhaustedException extends ReferencedEntityIdLookupException {

	/**
	 * @var int
	 */
	private $maxEntityVisits;

	/**
	 * @param EntityId $fromId
	 * @param PropertyId $propertyId
	 * @param EntityId[] $toIds
	 * @param int $maxEntityVisits
	 * @param string|null $message
	 * @param Exception|null $previous
	 */
	public function __construct(
		EntityId $fromId,
		PropertyId $propertyId,
		array $toIds,
		$maxEntityVisits,
		$message = null,
		Exception $previous = null
	) {
		$this->maxEntityVisits = $maxEntityVisits;
		$message = $message ?: 'Referenced entity id lookup failed: Maximum number of entity visits (' .
				$maxEntityVisits . ') exhausted.';

		parent::__construct( $fromId, $propertyId, $toIds, $message, $previous );
	}

	/**
	 * @return int
	 */
	public function getMaxEntityVisits() {
		return $this->maxEntityVisits;
	}

}

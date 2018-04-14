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
class MaxReferenceDepthExhaustedException extends ReferencedEntityIdLookupException {

	/**
	 * @var int
	 */
	private $maxDepth;

	/**
	 * @param EntityId $fromId
	 * @param PropertyId $propertyId
	 * @param EntityId[] $toIds
	 * @param int $maxDepth
	 * @param string|null $message
	 * @param Exception|null $previous
	 */
	public function __construct(
		EntityId $fromId,
		PropertyId $propertyId,
		array $toIds,
		$maxDepth,
		$message = null,
		Exception $previous = null
	) {
		$this->maxDepth = $maxDepth;
		$message = $message ?: 'Referenced entity id lookup failed: Maximum depth of ' . $maxDepth . ' exhausted.';

		parent::__construct( $fromId, $propertyId, $toIds, $message, $previous );
	}

	/**
	 * @return int
	 */
	public function getMaxDepth() {
		return $this->maxDepth;
	}

}

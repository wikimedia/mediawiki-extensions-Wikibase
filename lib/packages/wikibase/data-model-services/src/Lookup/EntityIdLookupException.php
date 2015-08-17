<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 1.2
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * Thrown to indicate that a lookup has failed.
 * This DOES NOT mean that the object does not exist.
 * The object asked for may exist but there is something preventing us from getting it right now.
 */
class EntityIdLookupException extends \RuntimeException {

	private $entityId;

	public function __construct( EntityId $entityId, $message = null, \Exception $previous = null ) {
		$this->entityId = $entityId;

		parent::__construct(
			$message ?: 'Entity lookup failed for: ' . $entityId,
			0,
			$previous
		);
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

}

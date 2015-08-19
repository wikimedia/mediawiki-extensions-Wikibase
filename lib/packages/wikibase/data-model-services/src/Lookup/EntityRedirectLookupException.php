<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 2.0
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class EntityRedirectLookupException extends \RuntimeException {

	private $entityId;

	public function __construct( EntityId $entityId, $message = null, \Exception $previous = null ) {
		$this->entityId = $entityId;

		parent::__construct(
			$message ?: 'Entity redirect lookup failed for: ' . $entityId,
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

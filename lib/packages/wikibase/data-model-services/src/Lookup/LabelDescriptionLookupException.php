<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 1.2
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class LabelDescriptionLookupException extends \RuntimeException {

	private $entityId;

	public function __construct( EntityId $entityId, $message = null, \Exception $previous = null ) {
		$this->entityId = $entityId;

		parent::__construct(
			$message ?: 'Label Description lookup failed for: ' . $entityId,
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

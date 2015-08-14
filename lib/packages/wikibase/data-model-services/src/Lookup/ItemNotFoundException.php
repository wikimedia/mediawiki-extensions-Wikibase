<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ItemNotFoundException extends EntityNotFoundException {

	public function __construct( ItemId $itemId, $message = null, \Exception $previous = null ) {
		parent::__construct(
			$itemId,
			$message ?: 'Item not found: ' . $itemId,
			$previous
		);
	}

	/**
	 * @return ItemId
	 */
	public function getItemId() {
		return $this->getEntityId();
	}

}

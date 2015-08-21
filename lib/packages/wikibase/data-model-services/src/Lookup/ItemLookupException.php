<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 2.0
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ItemLookupException extends EntityLookupException {

	public function __construct( ItemId $itemId, $message = null, \Exception $previous = null ) {
		parent::__construct(
			$itemId,
			$message ?: 'Item lookup failed for: ' . $itemId,
			$previous
		);
	}

	/**
	 * @return ItemId
	 */
	public function getItemId() {
		return parent::getEntityId();
	}

}

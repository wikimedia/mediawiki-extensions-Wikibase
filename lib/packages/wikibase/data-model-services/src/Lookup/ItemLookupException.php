<?php

namespace Wikibase\DataModel\Services\Lookup;

use Exception;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 2.0
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ItemLookupException extends EntityLookupException {

	/**
	 * @param ItemId $itemId
	 * @param string|null $message
	 * @param Exception|null $previous
	 */
	public function __construct( ItemId $itemId, $message = null, Exception $previous = null ) {
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
		return $this->getEntityId();
	}

}

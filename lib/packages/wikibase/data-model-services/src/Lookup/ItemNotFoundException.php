<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ItemNotFoundException extends \RuntimeException {

	private $itemId;

	public function __construct( ItemId $itemId, $message = null, \Exception $previous = null ) {
		$this->itemId = $itemId;

		parent::__construct(
			$message ?: 'Item not found: ' . $itemId,
			0,
			$previous
		);
	}

	/**
	 * @return ItemId
	 */
	public function getItemId() {
		return $this->itemId;
	}

}

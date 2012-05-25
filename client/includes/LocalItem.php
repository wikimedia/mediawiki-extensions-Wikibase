<?php

namespace Wikibase;
use ORMRow;

/**
 * Class representing a single local item (ie a row in the wbc_local_items).
 *
 * TODO: would be nice if this thing could use the decorator pattern and actually be
 * and extension to the item class, but for that we need an IItem interface in the lib.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LocalItem extends ORMRow {

	/**
	 * @since 0.1
	 *
	 * @return Item
	 */
	public function getItem() {
		return $this->getField( 'item_data' );
	}

}
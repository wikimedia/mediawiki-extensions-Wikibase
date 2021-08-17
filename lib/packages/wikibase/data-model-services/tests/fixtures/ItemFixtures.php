<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemFixtures {

	public static function newItem() {
		return new Item(
			new ItemId( 'Q1' )
		);
	}

}

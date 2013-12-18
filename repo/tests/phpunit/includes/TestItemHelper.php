<?php

namespace Wikibase\Test;

use Wikibase\Item;
use Wikibase\ItemContent;

/**
 * Helper class to create test items.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class TestItemHelper {

	public static function saveItem( Item $item ) {
		$content = ItemContent::newFromItem( $item );
		$content->save( "testing", null, EDIT_NEW );
	}

	public static function getTestItem() {
		static $item;

		if ( $item === null ) {
			$item = Item::newEmpty();
			$item->setLabel( 'en', 'Raarrr' );
			self::saveItem( $item );
		}

		return $item;
	}
}

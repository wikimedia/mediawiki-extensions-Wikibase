<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\ItemContent;

/**
 * Holds ItemContent objects for testing proposes.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class TestItemContents {

	/**
	 * @return ItemContent[]
	 */
	public static function getItems() {
		return array_map(
			'\Wikibase\ItemContent::newFromItem',
			self::getItemObjects()
		);
	}

	private static function getItemObjects() {
		$items = array();

		$items[] = Item::newEmpty();

		$item = Item::newEmpty();

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$items[] = $item;

		$item = Item::newEmpty();

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );

		$items[] = $item;

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'spam' ) );

		$items[] = $item;

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'spamz' ) );
		$item->addSiteLink( new SiteLink( 'dewiki', 'foobar' ) );

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );
		$item->addAliases( 'de', array( 'foobar', 'spam' ) );

		$items[] = $item;

		return $items;
	}

}

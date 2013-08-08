<?php

namespace Wikibase\Test;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;

/**
 * Holds Item objects for testing proposes.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class TestItems {

	/**
	 * @since 0.1
	 * @return Item[]
	 */
	public static function getItems() {
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
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'spam' ) );

		$items[] = $item;

		$item = Item::newEmpty();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'spamz' ) );
		$item->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'foobar' ) );

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );
		$item->addAliases( 'de', array( 'foobar', 'spam' ) );

		$items[] = $item;

		return $items;
	}

}
<?php

namespace Wikibase\test;
use \Wikibase\Item as Item;

/**
 * Holds item objects for testing proposes.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class TestItems {

	/**
	 * @since 0.1
	 * @return array of Item
	 */
	public static function getItems() {
		$items = array( Item::newEmpty() );
		$links = \Wikibase\Sites::singleton();

		$item = Item::newEmpty();

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$items[] = $item;

		$item = Item::newEmpty();

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );

		$items[] = $item;

		$item = Item::newEmpty();

		$item->addSiteLink( $links->current()->getId(), 'spam' );

		$items[] = $item;

		$item = Item::newEmpty();

		$item->addSiteLink( $links->current()->getId(), 'spam' );
		$links->next();
		$item->addSiteLink( $links->current()->getId(), 'foobar' );

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );
		$item->addAliases( 'de', array( 'foobar', 'spam' ) );

		$items[] = $item;

		return $items;
	}

}
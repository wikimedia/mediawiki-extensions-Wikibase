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


		$item = Item::newEmpty();

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$items[] = $item;

		$item = Item::newEmpty();

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );

		$items[] = $item;

		$item = Item::newEmpty();

		$links = \Wikibase\Sites::singleton()->getAllSites();

		if ( $links->count() > 1 ) {
			$item->addSiteLink( $links->getIterator()->current()->getId(), 'spam' );
		}

		$items[] = $item;

		$item = Item::newEmpty();

		if ( $links->count() > 1 ) {
			$linksIterator = $links->getIterator();

			$item->addSiteLink( $linksIterator->current()->getId(), 'spam' );
			$linksIterator->next();
			$item->addSiteLink( $linksIterator->current()->getId(), 'foobar' );
		}

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );
		$item->addAliases( 'de', array( 'foobar', 'spam' ) );

		$items[] = $item;

		return $items;
	}

}
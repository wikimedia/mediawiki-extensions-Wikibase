<?php

namespace Wikibase\Test;
use \Wikibase\Item as Item;
use \Wikibase\ItemObject as ItemObject;

/**
 * Holds Item objects for testing proposes.
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
		$items = array( ItemObject::newEmpty() );


		$item = ItemObject::newEmpty();

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$items[] = $item;

		$item = ItemObject::newEmpty();

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );

		$items[] = $item;

		$item = ItemObject::newEmpty();

		$links = \Wikibase\Sites::singleton()->getAllSites();

		if ( $links->count() > 1 ) {
			$item->addSiteLink( $links->getIterator()->current()->getId(), 'spam' );
		}

		$items[] = $item;

		$item = ItemObject::newEmpty();

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
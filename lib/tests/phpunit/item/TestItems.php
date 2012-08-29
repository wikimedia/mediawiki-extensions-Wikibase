<?php

namespace Wikibase\Test;
use \Wikibase\Item as Item;
use \Wikibase\ItemObject as ItemObject;
use \Wikibase\SiteLink as SiteLink;

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

		$sites = \Sites::singleton()->getSites();

		if ( count( $sites ) > 1 ) {
			$item->addSiteLink( new SiteLink( $sites->getIterator()->current(), 'spam' ) );
		}

		$items[] = $item;

		$item = ItemObject::newEmpty();

		if ( count( $sites ) > 1 ) {
			$linksIterator = $sites->getIterator();

			$item->addSiteLink( new SiteLink( $linksIterator->current(), 'spamz' ) );
			$linksIterator->next();
			$item->addSiteLink( new SiteLink( $linksIterator->current(), 'foobar' ) );
		}

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );
		$item->addAliases( 'de', array( 'foobar', 'spam' ) );

		$items[] = $item;

		return $items;
	}

}
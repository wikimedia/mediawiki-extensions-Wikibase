<?php

namespace Wikibase\DataModel\Tests\Entity;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;

/**
 * Holds Item objects for testing proposes.
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

		$items[] = new Item();

		$item = new Item();

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$items[] = $item;

		$item = new Item();

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );

		$items[] = $item;

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'spam' );

		$items[] = $item;

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'spamz' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'foobar' );

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );
		$item->addAliases( 'de', array( 'foobar', 'spam' ) );

		$items[] = $item;

		return $items;
	}

}

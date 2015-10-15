<?php

namespace Wikibase\DataModel\Tests\Entity;

use Wikibase\DataModel\Entity\Item;

/**
 * Holds Item objects for testing proposes.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class TestItems {

	/**
	 * @since 0.1
	 *
	 * @return Item[]
	 */
	public static function getItems() {
		$withTerms = new Item();
		$withTerms->setDescription( 'en', 'foo' );
		$withTerms->setLabel( 'en', 'bar' );

		$withAlias = new Item();
		$withAlias->addAliases( 'en', array( 'foobar', 'baz' ) );

		$withSiteLink = new Item();
		$withSiteLink->getSiteLinkList()->addNewSiteLink( 'enwiki', 'spam' );

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'spamz' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'foobar' );
		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );
		$item->addAliases( 'en', array( 'foobar', 'baz' ) );
		$item->addAliases( 'de', array( 'foobar', 'spam' ) );

		return array(
			'Empty' => new Item(),
			'Label and description' => $withTerms,
			'Alias only' => $withAlias,
			'SiteLink only' => $withSiteLink,
			'Everything but statements' => $item,
		);
	}

}

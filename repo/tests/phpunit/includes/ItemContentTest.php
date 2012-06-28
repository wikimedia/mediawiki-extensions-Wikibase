<?php

namespace Wikibase\Test;
use \Wikibase\ItemContent as ItemContent;
use \Wikibase\Item as Item;

/**
 * Tests for the WikibaseItem class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemContentTest extends \MediaWikiTestCase {

	/**
	 * Tests @see WikibaseItem::getIdForSiteLink
	 */
	public function testNotFound() {
		$this->assertFalse(
			ItemContent::getIdForSiteLink( 9999, "ThisDoesNotExistAndProbablyWillNeverExist" ),
			'Calling getIdForLinkSite( 42, "ThisDoesNotExistAndProbablyWillNeverExist" ) should return false'
		);
	}

	/**
	 * Tests @see WikibaseItem::getTitleForId
	 */
	public function testGetTitleForId() {
		$title = ItemContent::getTitleForId( 42 );
		$this->assertInstanceOf(
			'\Title',
			$title,
			'Calling WikibaseItem::getTitleForId(42) should return a Title object'
		);
		$this->assertRegExp(
			'/Q42/i',
			$title->getBaseText(),
			'Calling getBaseText() on returned Title from WikibaseItem::getTitleForId(42), ie either a new item with this id or an existing, should return number 42'
		);
	}

	/**
	 * Tests @see WikibaseItem::getWikiPageForId
	 */
	public function testGetWikiPageForId() {
		$page = ItemContent::getWikiPageForId( 42 );
		$this->assertInstanceOf(
			'\WikiPage',
			$page,
			'Calling WikibaseItem::getWikiPageForId(42) should return a WikiPage object'
		);
		$this->assertRegExp(
			'/Q42/i',
			$page->getTitle()->getBaseText(),
			'Calling getTitle()->getBaseText() on returned WikiPage from WikibaseItem::getTitleForId(42), ie either a new item with this id or an existing, should return number 42'
		);
	}

	public function dataGetTextForSearchIndex() {
		return array( // runs
			array( // #0
				array( // data
					'label' => array( 'en' => 'Test', 'de' => 'Testen' ),
					'aliases' => array( 'en' => array( 'abc', 'cde' ), 'de' => array( 'xyz', 'uvw' ) )
				),
				array( // patterns
					'/^Test$/',
					'/^Testen$/',
					'/^abc$/',
					'/^cde$/',
					'/^uvw$/',
					'/^xyz$/',
					'/^(?!abcde).*$/',
				),
			),
		);
	}

	/**
	 * Tests @see WikibaseItem::getTextForSearchIndex
	 *
	 * @dataProvider dataGetTextForSearchIndex
	 *
	 * @param array $data
	 * @param array $patterns
	 */
	public function testGetTextForSearchIndex( array $data, array $patterns ) {
		$item = ItemContent::newFromArray( $data );
		$text = $item->getTextForSearchIndex();

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern . 'm', $text );
		}
	}
}
	
<?php

namespace Wikibase\Test;
use \Wikibase\ItemContent as ItemContent;
use \Wikibase\Item as Item;

/**
 * Tests for the Wikibase\ItemContent class.
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
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class ItemContentTest extends \MediaWikiTestCase {

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

	public function testRepeatedSave() {
		$itemContent = \Wikibase\ItemContent::newEmpty();

		// create
		$itemContent->getItem()->setLabel( 'en', "First" );
		$status = $itemContent->save( 'create item', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertTrue( $status->isGood(), $status->getMessage() );

		// change
		$prev_id = $itemContent->getWikiPage()->getLatest();
		$itemContent->getItem()->setLabel( 'en', "Second" );
		$status = $itemContent->save( 'modify item', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertTrue( $status->isGood(), $status->getMessage() );
		$this->assertNotEquals( $prev_id, $itemContent->getWikiPage()->getLatest(), "revision ID should change on edit" );

		// change again
		$prev_id = $itemContent->getWikiPage()->getLatest();
		$itemContent->getItem()->setLabel( 'en', "Third" );
		$status = $itemContent->save( 'modify item again', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertTrue( $status->isGood(), $status->getMessage() );
		$this->assertNotEquals( $prev_id, $itemContent->getWikiPage()->getLatest(), "revision ID should change on edit" );

		// save unchanged
		$prev_id = $itemContent->getWikiPage()->getLatest();
		$status = $itemContent->save( 'save unmodified', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertEquals( $prev_id, $itemContent->getWikiPage()->getLatest(), "revision ID should stay the same if no change was made" );
	}
}
	
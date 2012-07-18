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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
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
}
	
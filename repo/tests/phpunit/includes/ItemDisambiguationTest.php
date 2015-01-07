<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\ItemDisambiguation;

/**
 * @covers Wikibase\ItemDisambiguation
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ItemDisambiguationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param string $searchLanguageCode
	 * @param string $userLanguageCode
	 *
	 * @return ItemDisambiguation
	 */
	private function newItemDisambiguation( $searchLanguageCode, $userLanguageCode ) {
		$entityIdFormatter = $this->getMock( 'Wikibase\Lib\EntityIdFormatter' );

		$entityIdFormatter->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnCallback( function( ItemId $itemId ) {
				return $itemId->getSerialization();
			} ) );

		return new ItemDisambiguation(
			$searchLanguageCode,
			$userLanguageCode,
			$entityIdFormatter
		);
	}

	public function getHTMLProvider() {
		$one = new Item( new ItemId( 'Q1' ) );
		$one->setLabel( 'en', 'one' );
		$one->setLabel( 'de', 'eins' );
		$one->setDescription( 'en', 'number' );
		$one->setDescription( 'de', 'Zahl' );

		$oneone = new Item( new ItemId( 'Q11' ) );
		$oneone->setLabel( 'en', 'oneone' );
		$oneone->setLabel( 'de', 'einseins' );

		$cases = array();
		$matchers = array();

		$matchers['matches'] = array(
			'tag' => 'ul',
			'content' => '',
			'attributes' => array( 'class' => 'wikibase-disambiguation' ),
		);

		$cases['empty'] = array( 'en', 'en', array(), $matchers );

		// en/one
		$matchers['matches'] = array(
			'tag' => 'ul',
			'children' => array( 'count' => 2 ),
			'attributes' => array( 'class' => 'wikibase-disambiguation' ),
		);

		$matchers['one'] = array(
			'tag' => 'li',
			'content' => 'regexp:/^Q1[^1]/s',
		);

		$matchers['one/desc'] = array(
			'tag' => 'span',
			'content' => 'number',
			'attributes' => array( 'class' => 'wb-itemlink-description' ),
		);

		$matchers['oneone'] = array(
			'tag' => 'li',
			'content' => 'regexp:/^Q11/s',
		);

		$matchers['oneone/desc'] = array(
			'tag' => 'span',
			//'content' => 'Q11',
			'attributes' => array( 'class' => 'wb-itemlink-description' ),
		);

		$cases['en/one'] = array( 'en', 'en', array( $one, $oneone ), $matchers );

		// de/eins
		$matchers['one/de'] = array(
			'tag' => 'span',
			'parent' => array( 'tag' => 'li' ),
			'content' => 'eins',
			'attributes' => array( 'lang' => 'de' ),
		);

		$matchers['oneone/de'] = array(
			'tag' => 'span',
			//'parent' => array( 'tag' => 'li' ), // PHPUnit's assertTag doesnt like this here
			'content' => 'einseins',
			'attributes' => array( 'lang' => 'de' ),
		);

		$cases['de/eins'] = array( 'de', 'en', array( $one, $oneone ), $matchers );

		return $cases;
	}

	/**
	 * @dataProvider getHTMLProvider
	 */
	public function testGetHTML( $searchLanguageCode, $userLanguageCode, array $items, array $matchers ) {
		$disambig = $this->newItemDisambiguation( $searchLanguageCode, $userLanguageCode );

		$html = $disambig->getHTML( $items );

		foreach ( $matchers as $key => $matcher ) {
			\MediaWikiTestCase::assertTag( $matcher, $html, "Failed to match HTML output with tag '{$key}'" );
		}
	}

}

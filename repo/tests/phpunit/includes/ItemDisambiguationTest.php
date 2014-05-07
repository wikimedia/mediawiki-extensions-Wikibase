<?php

namespace Wikibase\Test;

use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\EntityIdFormatter;

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

	protected function newItemDisambiguation( $searchLang, $userLang ) {
		$disambig = new ItemDisambiguation(
			$searchLang,
			$userLang,
			new EntityIdFormatter( new FormatterOptions() )
		);

		return $disambig;
	}

	public function getHTMLProvider() {
		$one = Item::newEmpty();
		$one->setId( new ItemId( 'Q1' ) );
		$one->setLabel( 'en', 'one' );
		$one->setLabel( 'de', 'eins' );
		$one->setDescription( 'en', 'number' );
		$one->setDescription( 'de', 'Zahl' );

		$oneone = Item::newEmpty();
		$oneone->setId( new ItemId( 'Q11' ) );
		$oneone->setLabel( 'en', 'oneone' );
		$oneone->setLabel( 'de', 'einseins' );


		$cases = array();
		$matchers = array();

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
	public function testGetHTML( $searchLang, $userLang, $items, $matchers ) {
		$disambig = $this->newItemDisambiguation( $searchLang, $userLang );

		$html = $disambig->getHTML( $items );

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $html, "Failed to match html output with tag '{$key}''" );
		}
	}

}

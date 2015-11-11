<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * @covers Wikibase\ItemDisambiguation
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class ItemDisambiguationTest extends \MediaWikiTestCase {

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockEntityTitleLookup() {
		$entityIdFormatter = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$entityIdFormatter->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( ItemId $id ) {
				return Title::makeTitle( NS_MAIN, $id->getSerialization() );
			} ) );

		return $entityIdFormatter;
	}

	/**
	 * @return ItemDisambiguation
	 */
	private function newItemDisambiguation() {
		$languageNameLookup = $this->getMock( 'Wikibase\Lib\LanguageNameLookup' );

		return new ItemDisambiguation(
			$this->getMockEntityTitleLookup(),
			$languageNameLookup,
			'en'
		);
	}

	public function getHTMLProvider() {
		$cases = array();
		$matchers = array();

		// No results
		$matchers['matches'] = array(
			'tag' => 'ul',
			'content' => '',
			'attributes' => array( 'class' => 'wikibase-disambiguation' ),
		);
		$cases['No Results'] = array( array(), $matchers );

		// One label match in the display language
		$matchers['matches'] = array(
			'tag' => 'ul',
			'children' => array( 'count' => 1 ),
			'attributes' => array( 'class' => 'wikibase-disambiguation' ),
		);
		$matchers['one'] = array(
			'tag' => 'li',
			'content' => 'regexp:/^Q1[^1]/s',
		);
		$matchers['one/label'] = array(
			'tag' => 'span',
			'content' => 'Foo',
			'attributes' => array( 'class' => 'wb-itemlink-label' ),
		);
		$matchers['one/desc'] = array(
			'tag' => 'span',
			'content' => 'DisplayDescription',
			'attributes' => array( 'class' => 'wb-itemlink-description' ),
		);
		$cases['One label match in the display language'] = array(
			array(
				new TermSearchResult(
					new Term( 'en', 'Foo' ),
					'label',
					new ItemId( 'Q1' ),
					new Term( 'en', 'Foo' ),
					new Term( 'en', 'DisplayDescription' )
				),
			),
			$matchers
		);

		// One alias match of another language
		$matchers['matches'] = array(
			'tag' => 'ul',
			'children' => array( 'count' => 1 ),
			'attributes' => array( 'class' => 'wikibase-disambiguation' ),
		);
		$matchers['one'] = array(
			'tag' => 'li',
			'content' => 'regexp:/^Q1[^1]/s',
		);
		$matchers['one/label'] = array(
			'tag' => 'span',
			'content' => 'DisplayLabel',
			'attributes' => array( 'class' => 'wb-itemlink-label' ),
		);
		$matchers['one/desc'] = array(
			'tag' => 'span',
			'content' => 'DisplayDescription',
			'attributes' => array( 'class' => 'wb-itemlink-description' ),
		);
		$matchers['one/match'] = array(
			'tag' => 'span',
			'content' => 'regexp:/Foo/s',
			'attributes' => array( 'class' => 'wb-itemlink-match' ),
		);
		$cases['One alias match of another language'] = array(
			array(
				new TermSearchResult(
					new Term( 'de', 'Foo' ),
					'alias',
					new ItemId( 'Q1' ),
					new Term( 'en', 'DisplayLabel' ),
					new Term( 'en', 'DisplayDescription' )
				),
			),
			$matchers
		);

		// Two Results - (1 - No Label in display Language, 2 - No Description)
		unset( $matchers['one/label'] );
		$matchers['matches'] = array(
			'tag' => 'ul',
			'children' => array( 'count' => 2 ),
			'attributes' => array( 'class' => 'wikibase-disambiguation' ),
		);
		$matchers['one'] = array(
			'tag' => 'li',
			'content' => 'regexp:/^Q2[^1]/s',
		);
		$matchers['one/desc'] = array(
			'tag' => 'span',
			'content' => 'DisplayDescription',
			'attributes' => array( 'class' => 'wb-itemlink-description' ),
		);
		$matchers['one/match'] = array(
			'tag' => 'span',
			'content' => 'regexp:/Foo/s',
			'attributes' => array( 'class' => 'wb-itemlink-match' ),
		);
		$matchers['two'] = array(
			'tag' => 'li',
			'content' => 'regexp:/^Q3[^1]/s',
		);
		$matchers['two/match'] = array(
			'tag' => 'span',
			'content' => 'regexp:/Foo/s',
			'attributes' => array( 'class' => 'wb-itemlink-match' ),
		);
		$cases['Two Results'] = array(
			array(
				new TermSearchResult(
					new Term( 'de', 'Foo' ),
					'label',
					new ItemId( 'Q2' ),
					null,
					new Term( 'en', 'DisplayDescription' )
				),
				new TermSearchResult(
					new Term( 'de', 'Foo' ),
					'label',
					new ItemId( 'Q3' ),
					new Term( 'en', 'DisplayLabel' )
				),
			),
			$matchers
		);

		return $cases;
	}

	/**
	 * @dataProvider getHTMLProvider
	 */
	public function testGetHTML( array $searchResults, array $matchers ) {
		$disambig = $this->newItemDisambiguation();

		$html = $disambig->getHTML( $searchResults );

		foreach ( $matchers as $key => $matcher ) {
			MediaWikiTestCase::assertTag( $matcher, $html, "Failed to match HTML output with tag '{$key}'" );
		}
	}

}

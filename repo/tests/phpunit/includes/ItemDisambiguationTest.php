<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\TermIndexEntry;

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
	 * @return EntityIdFormatter
	 */
	private function getMockEntityIdFormatter() {
		$entityIdFormatter = $this->getMock( 'Wikibase\Lib\EntityIdFormatter' );
		$entityIdFormatter->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnCallback( function( ItemId $itemId ) {
				return $itemId->getSerialization();
			} ) );
		return $entityIdFormatter;
	}

	/**
	 * @return ItemDisambiguation
	 */
	private function newItemDisambiguation() {
		return new ItemDisambiguation(
			$this->getMockEntityIdFormatter(),
			new LanguageNameLookup(),
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

		// One Normal Result
		$matchers['matches'] = array(
			'tag' => 'ul',
			'children' => array( 'count' => 1 ),
			'attributes' => array( 'class' => 'wikibase-disambiguation' ),
		);
		$matchers['one'] = array(
			'tag' => 'li',
			'content' => 'regexp:/^Q1[^1]/s',
		);
		$matchers['one/desc'] = array(
			'tag' => 'span',
			'content' => 'DisplayDescription',
			'attributes' => array( 'class' => 'wb-itemlink-description' ),
		);
		$cases['One Normal Result'] = array(
			array(
				array(
					'entityId' => new ItemId( 'Q1' ),
					'matchedTerm' => new Term( 'de', 'Foo'),
					'matchedTermType' => array( 'label' ),
					'displayTerms' => array(
						TermIndexEntry::TYPE_LABEL => new Term( 'en', 'DisplayLabel' ),
						TermIndexEntry::TYPE_DESCRIPTION => new Term( 'en', 'DisplayDescription' ),
						),
					),
				),
			$matchers
		);

		// Two Results - (1 - No Label in display Language, 2 - No Description)
		$matchers['matches'] = array(
			'tag' => 'ul',
			'children' => array( 'count' => 2 ),
			'attributes' => array( 'class' => 'wikibase-disambiguation' ),
		);
		$matchers['one'] = array(
			'tag' => 'li',
			'content' => 'regexp:/^Q2[^1]/s',
		);
		$matchers['one/label'] = array(
			'tag' => 'span',
			'content' => 'Foo',
			'attributes' => array( 'class' => 'wb-itemlink-query-lang', 'lang' => 'de' ),
		);
		$matchers['one/desc'] = array(
			'tag' => 'span',
			'content' => 'DisplayDescription',
			'attributes' => array( 'class' => 'wb-itemlink-description' ),
		);
		$matchers['two'] = array(
			'tag' => 'li',
			'content' => 'regexp:/^Q3[^1]/s',
		);
		$matchers['two/desc'] = array(
			'tag' => 'span',
			'content' => 'Q3',
			'attributes' => array( ),
		);
		$cases['Two Results'] = array(
			array(
				array(
					'entityId' => new ItemId( 'Q2' ),
					'matchedTermType' => 'label',
					'matchedTerm' => new Term( 'de', 'Foo' ),
					'displayTerms' => array(
						TermIndexEntry::TYPE_DESCRIPTION => new Term( 'en', 'DisplayDescription' ),
					),
				),
				array(
					'entityId' => new ItemId( 'Q3' ),
					'matchedTermType' => 'label',
					'matchedTerm' => new Term( 'de', 'Foo' ),
					'displayTerms' => array(
						TermIndexEntry::TYPE_LABEL => new Term( 'en', 'DisplayLabel' ),
					),
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

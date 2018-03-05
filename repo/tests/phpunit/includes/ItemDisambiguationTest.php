<?php

namespace Wikibase\Repo\Tests;

use MediaWikiTestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @covers Wikibase\ItemDisambiguation
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ItemDisambiguationTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setUserLang( 'qqx' );
	}

	/**
	 * @return ItemDisambiguation
	 */
	private function newInstance() {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( $this->getMock( Title::class ) ) );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( '<LANG>' ) );

		return new ItemDisambiguation(
			$entityTitleLookup,
			$languageNameLookup,
			'en'
		);
	}

	public function testNoResults() {
		$html = $this->newInstance()->getHTML( [] );

		$this->assertSame( '<ul class="wikibase-disambiguation"></ul>', $html );
	}

	public function testOneResult() {
		$searchResult = new TermSearchResult(
			new Term( 'en', '<MATCH>' ),
			'<TYPE>',
			new ItemId( 'Q1' ),
			new Term( 'en', '<LABEL>' ),
			new Term( 'en', '<DESC>' )
		);
		$html = $this->newInstance()->getHTML( [ $searchResult ] );

		$this->assertContains( '<ul class="wikibase-disambiguation">', $html );
		$this->assertSame( 1, substr_count( $html, '<li ' ) );

		$this->assertContains( '>Q1</a>', $html );
		$this->assertContains( '<span class="wb-itemlink-label">&lt;LABEL></span>', $html );
		$this->assertContains( '<span class="wb-itemlink-description">&lt;DESC></span>', $html );
		$this->assertContains( '(wikibase-itemlink-userlang-wrapper: &lt;LANG>, &lt;MATCH>)',
			$html
		);
	}

	public function testTwoResults() {
		$searchResults = [
			new TermSearchResult(
				new Term( 'de', '<MATCH1>' ),
				'<TYPE1>',
				new ItemId( 'Q1' ),
				null,
				new Term( 'en', '<DESC1>' )
			),
			new TermSearchResult(
				new Term( 'de', '<MATCH2>' ),
				'<TYPE2>',
				new ItemId( 'Q2' ),
				new Term( 'en', '<LABEL2>' )
			),
		];
		$html = $this->newInstance()->getHTML( $searchResults );

		$this->assertContains( '<ul class="wikibase-disambiguation">', $html );
		$this->assertSame( 2, substr_count( $html, '<li ' ) );

		$this->assertContains( '>Q1</a>', $html );
		$this->assertContains( '<span class="wb-itemlink-description">&lt;DESC1></span>', $html );
		$this->assertContains( '(wikibase-itemlink-userlang-wrapper: &lt;LANG>, &lt;MATCH1>)',
			$html
		);

		$this->assertContains( '>Q2</a>', $html );
		$this->assertContains( '<span class="wb-itemlink-label">&lt;LABEL2></span>', $html );
		$this->assertContains( '(wikibase-itemlink-userlang-wrapper: &lt;LANG>, &lt;MATCH2>)',
			$html
		);
	}

}

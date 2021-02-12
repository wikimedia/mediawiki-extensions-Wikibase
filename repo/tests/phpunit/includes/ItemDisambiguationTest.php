<?php

namespace Wikibase\Repo\Tests;

use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\ItemDisambiguation;

/**
 * @covers \Wikibase\Repo\ItemDisambiguation
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ItemDisambiguationTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->setUserLang( 'qqx' );
	}

	/**
	 * @return ItemDisambiguation
	 */
	private function newInstance() {
		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );
		$entityTitleLookup->method( 'getTitleForId' )
			->willReturn( $this->createMock( Title::class ) );

		$languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$languageNameLookup->method( 'getName' )
			->willReturn( '<LANG>' );

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

		$this->assertStringContainsString( '<ul class="wikibase-disambiguation">', $html );
		$this->assertSame( 1, substr_count( $html, '<li ' ) );

		$this->assertStringContainsString( '>Q1</a>', $html );
		$this->assertStringContainsString( '<span class="wb-itemlink-label">&lt;LABEL></span>', $html );
		$this->assertStringContainsString( '<span class="wb-itemlink-description">&lt;DESC></span>', $html );
		$this->assertStringContainsString( '(wikibase-itemlink-userlang-wrapper: &lt;LANG>, &lt;MATCH>)',
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

		$this->assertStringContainsString( '<ul class="wikibase-disambiguation">', $html );
		$this->assertSame( 2, substr_count( $html, '<li ' ) );

		$this->assertStringContainsString( '>Q1</a>', $html );
		$this->assertStringContainsString( '<span class="wb-itemlink-description">&lt;DESC1></span>', $html );
		$this->assertStringContainsString( '(wikibase-itemlink-userlang-wrapper: &lt;LANG>, &lt;MATCH1>)',
			$html
		);

		$this->assertStringContainsString( '>Q2</a>', $html );
		$this->assertStringContainsString( '<span class="wb-itemlink-label">&lt;LABEL2></span>', $html );
		$this->assertStringContainsString( '(wikibase-itemlink-userlang-wrapper: &lt;LANG>, &lt;MATCH2>)',
			$html
		);
	}

}

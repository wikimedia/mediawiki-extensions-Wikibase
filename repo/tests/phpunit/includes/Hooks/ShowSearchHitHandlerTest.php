<?php

namespace Wikibase\Repo\Tests\Hooks;

use Language;
use MediaWikiTestCase;
use RawMessage;
use SearchResult;
use SpecialSearch;
use Title;
use Wikibase\Repo\Hooks\ShowSearchHitHandler;
use Wikibase\Repo\Search\Elastic\EntityResult;

/**
 * @covers \Wikibase\Repo\Hooks\ShowSearchHitHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Matěj Suchánek
 */
class ShowSearchHitHandlerTest extends MediaWikiTestCase {

	/**
	 * Test cases that should be covered:
	 * - non-Entity result
	 * - name+description
	 * - name missing
	 * - description missing
	 * - both missing
	 * - name+description with extra data
	 * - name + description in different language
	 * - name + description + extra data in different language
	 */

	public function showSearchHitProvider() {
		return [
			'label hit' => [
				// label
				[ 'language' => 'en', 'value' => 'Hit item' ],
				// description
				[ 'language' => 'en', 'value' => 'Hit description' ],
				// Highlighted label
				[ 'language' => 'en', 'value' => 'Hit !HERE! item' ],
				// Highlighted description
				[ 'language' => 'en', 'value' => 'Hit !HERE! description' ],
				// extra
				null,
				// statements
				1,
				// links
				2,
				// test name
				'labelHit'
			],
			'desc hit other language' => [
				// label
				[ 'language' => 'en', 'value' => 'Hit <escape me> item' ],
				// description
				[ 'language' => 'de', 'value' => 'Hit <here> "some" description' ],
				// Highlighted label
				[ 'language' => 'en', 'value' => 'Hit <escape me> item' ],
				// Highlighted description
				[ 'language' => 'de', 'value' => new \HtmlArmor( 'Hit <b>!HERE!</b> "some" description' ) ],
				// extra
				null,
				// statements
				1,
				// links
				2,
				// test name
				'descHit'
			],
			'label hit in other language' => [
				// label
				[ 'language' => 'en', 'value' => 'Hit item' ],
				// description
				[ 'language' => 'en', 'value' => 'Hit description' ],
				// Highlighted label
				[ 'language' => 'de', 'value' => 'Der hit !HERE! item' ],
				// Highlighted description
				[ 'language' => 'en', 'value' => 'Hit description' ],
				// extra
				null,
				// statements
				1,
				// links
				2,
				// test name
				'labelHitDe'
			],
			'description from fallback' => [
				// label
				[ 'language' => 'en', 'value' => 'Hit item' ],
				// description
				[ 'language' => 'de', 'value' => 'Beschreibung <"here">' ],
				// Highlighted label
				[ 'language' => 'de', 'value' => 'Der hit !HERE! item' ],
				// Highlighted description
				[ 'language' => 'de', 'value' => 'Beschreibung <"here">' ],
				// extra
				null,
				// statements
				3,
				// links
				4,
				// test name
				'labelHitDescDe'
			],
			'no label and desc' => [
				// label
				[ 'language' => 'en', 'value' => '' ],
				// description
				[ 'language' => 'de', 'value' => '' ],
				// Highlighted label
				[ 'language' => 'en', 'value' => '' ],
				// Highlighted description
				[ 'language' => 'de', 'value' => '' ],
				// extra
				null,
				// statements
				0,
				// links
				0,
				// test name
				'emptyLabel'
			],
			'extra data' => [
				// label
				[ 'language' => 'en', 'value' => 'Hit item' ],
				// description
				[ 'language' => 'en', 'value' => 'Hit description' ],
				// Highlighted label
				[ 'language' => 'en', 'value' => 'Hit item' ],
				// Highlighted description
				[ 'language' => 'en', 'value' => 'Hit description' ],
				// extra
				[ 'language' => 'en', 'value' => 'Look <what> I found!' ],
				// statements
				1,
				// links
				2,
				// test name
				'extra'
			],
			'extra data different language' => [
				// label
				[ 'language' => 'en', 'value' => 'Hit item' ],
				// description
				[ 'language' => 'en', 'value' => 'Hit description' ],
				// Highlighted label
				[ 'language' => 'en', 'value' => 'Hit item' ],
				// Highlighted description
				[ 'language' => 'en', 'value' => 'Hit description' ],
				// extra
				[ 'language' => 'ru', 'value' => new \HtmlArmor( 'Look <b>what</b> I found!' ) ],
				// statements
				1,
				// links
				2,
				// test name
				'extraLang'
			],
			'all languages' => [
				// label
				[ 'language' => 'ar', 'value' => 'Hit item' ],
				// description
				[ 'language' => 'he', 'value' => 'Hit description' ],
				// Highlighted label
				[ 'language' => 'es', 'value' => 'Hit !HERE! item' ],
				// Highlighted description
				[ 'language' => 'ru', 'value' => 'Hit !HERE! description' ],
				// extra
				[ 'language' => 'de', 'value' => 'Look <what> I found!' ],
				// statements
				100,
				// links
				200,
				// test name
				'manyLang'
			],
			'all languages 2' => [
				// label
				[ 'language' => 'de', 'value' => 'Hit item' ],
				// description
				[ 'language' => 'ru', 'value' => 'Hit description' ],
				// Highlighted label
				[ 'language' => 'fa', 'value' => 'Hit !HERE! item' ],
				// Highlighted description
				[ 'language' => 'he', 'value' => 'Hit !HERE! description' ],
				// extra
				[ 'language' => 'ar', 'value' => 'Look <what> I found!' ],
				// statements
				100,
				// links
				200,
				// test name
				'manyLang2'
			],
		];
	}

	/**
	 * @param string[] $labelData Source label, best match for display language
	 * @param string[] $descriptionData Source description, best match for display language
	 * @param string[] $labelHighlightedData Actual label match, with highlighting
	 * @param string[] $descriptionHighlightedData Actual description match, with highlighting
	 * @param string[] $extra Extra match data
	 * @param int $statementCount
	 * @param int $linkCount
	 * @return EntityResult
	 */
	private function getEntityResult(
		$labelData,
		$descriptionData,
		$labelHighlightedData,
		$descriptionHighlightedData,
		$extra,
		$statementCount,
		$linkCount
	) {
		$result = $this->getMockBuilder( EntityResult::class )
			->disableOriginalConstructor()->setMethods( [
				'getExtraDisplay',
				'getStatementCount',
				'getSitelinkCount',
				'getDescriptionData',
				'getLabelData',
				'getDescriptionHighlightedData',
				'getLabelHighlightedData',
			] )
			->getMock();

		$result->method( 'getExtraDisplay' )->willReturn( $extra );
		$result->method( 'getStatementCount' )->willReturn( $statementCount );
		$result->method( 'getSitelinkCount' )->willReturn( $linkCount );
		$result->method( 'getLabelData' )->willReturn( $labelData );
		$result->method( 'getDescriptionData' )->willReturn( $descriptionData );
		$result->method( 'getLabelHighlightedData' )->willReturn( $labelHighlightedData );
		$result->method( 'getDescriptionHighlightedData' )
			->willReturn( $descriptionHighlightedData );

		return $result;
	}

	/**
	 * @return SpecialSearch
	 */
	private function getSearchPage() {
		$searchPage = $this->getMockBuilder( SpecialSearch::class )
			->disableOriginalConstructor()
			->getMock();
		$searchPage->method( 'msg' )
			->willReturnCallback(
				function () {
					return new RawMessage( implode( ",", func_get_args() ) );
				}
			);
		$searchPage->method( 'getLanguage' )
			->willReturn( Language::factory( 'en' ) );

		return $searchPage;
	}

	/**
	 * @param string $title
	 *
	 * @return SearchResult
	 */
	private function getSearchResult( $title ) {
		$mockTitle = $this->getMock( Title::class );
		$mockTitle->method( 'getText' )
			->willReturn( $title );
		// hack: content model equals title/id
		$mockTitle->method( 'getContentModel' )
			->willReturn( $title );

		$searchResult = $this->getMockBuilder( SearchResult::class )
			->disableOriginalConstructor()
			->getMock();
		$searchResult->method( 'getTitle' )
			->willReturn( $mockTitle );

		return $searchResult;
	}

	public function testShowSearchHitNonEntity() {
		$searchPage = $this->getSearchPage();
		$link = '<a>link</a>';
		$extract = '<span>extract</span>';
		$redirect = $section = $score = $size = $date = $related = $html = '';
		$searchResult = $this->getMock( SearchResult::class );
		ShowSearchHitHandler::onShowSearchHit(
			$searchPage,
			$searchResult,
			[],
			$link,
			$redirect,
			$section,
			$extract,
			$score,
			$size,
			$date,
			$related,
			$html
		);
		$this->assertEquals( '<a>link</a>', $link );
		$this->assertEquals( '<span>extract</span>', $extract );
	}

	/**
	 * @dataProvider showSearchHitProvider
	 * @param $labelData
	 * @param $descriptionData
	 * @param $labelHighlightedData
	 * @param $descriptionHighlightedData
	 * @param $extra
	 * @param $statementCount
	 * @param $linkCount
	 */
	public function testShowSearchHit(
		$labelData,
		$descriptionData,
		$labelHighlightedData,
		$descriptionHighlightedData,
		$extra,
		$statementCount,
		$linkCount,
		$expected
	) {
		$testFile = __DIR__ . '/../../data/searchHits/' . $expected . ".html";

		$searchPage = $this->getSearchPage();
		$searchResult = $this->getEntityResult(
			$labelData,
			$descriptionData,
			$labelHighlightedData,
			$descriptionHighlightedData,
			$extra,
			$statementCount,
			$linkCount
		);
		$link = '<a>link</a>';
		$extract = '<span>extract</span>';
		$redirect = $section = $score = $size = $date = $related = $html = '';
		$title = "TITLE";
		$attributes = [ 'previous' => 'attrib' ];
		$query = [];

		ShowSearchHitHandler::onShowSearchHitTitle(
			Title::newFromText( 'Q1' ),
			$title,
			$searchResult,
			"",
			$searchPage,
			$query,
			$attributes
		);

		ShowSearchHitHandler::onShowSearchHit(
			$searchPage,
			$searchResult,
			[],
			$link,
			$redirect,
			$section,
			$extract,
			$score,
			$size,
			$date,
			$related,
			$html
		);
		$output = \HtmlArmor::getHtml( $title ) . "\n" .
				json_encode( $attributes, JSON_PRETTY_PRINT ) . "\n" .
				$section . "\n" .
				$extract . "\n" .
				$size;

		if ( file_exists( $testFile ) ) {
			$this->assertStringEqualsFile( $testFile, $output );
		} else {
			file_put_contents( $testFile, $output );
			$this->markTestSkipped( "Expected result not found" );
		}
	}

}

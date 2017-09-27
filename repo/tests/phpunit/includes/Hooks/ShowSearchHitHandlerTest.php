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
	 * Test cases:
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
			'name hit' => [
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
				'nameHit'
			],
			'desc hit other language' => [
				// label
				[ 'language' => 'en', 'value' => 'Hit <escape me> item' ],
				// description
				[ 'language' => 'de', 'value' => 'Hit <here> "some" description' ],
				// Highlighted label
				[ 'language' => 'en', 'value' =>  'Hit <escape me> item' ],
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
		$output = \HtmlArmor::getHtml($title) .
		          json_encode($attributes, JSON_PRETTY_PRINT) . "\n" .
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

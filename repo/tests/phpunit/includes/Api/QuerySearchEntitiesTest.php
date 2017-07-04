<?php

namespace Wikibase\Repo\Tests\Api;

use ApiContinuationManager;
use ApiMain;
use ApiPageSet;
use ApiQuery;
use FauxRequest;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\EntitySearchTermIndex;
use Wikibase\Repo\Api\QuerySearchEntities;

/**
 * @covers Wikibase\Repo\Api\QuerySearchEntities
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class QuerySearchEntitiesTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param array $params
	 *
	 * @return ApiQuery
	 */
	private function getApiQuery( array $params ) {
		$context = new RequestContext();
		$context->setLanguage( 'en-ca' );
		$context->setRequest( new FauxRequest( $params, true ) );
		$main = new ApiMain( $context );
		return new ApiQuery( $main, 'wbsearch' );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( $this->getMockTitle() ) );

		return $titleLookup;
	}

	/**
	 * @return ContentLanguages
	 */
	private function getContentLanguages() {
		return new StaticContentLanguages(
			[ 'de', 'de-ch', 'en', 'ii', 'nn', 'ru', 'zh-cn' ]
		);
	}

	/**
	 * @return Title
	 */
	public function getMockTitle() {
		$mock = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );
		$mock->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'Prefixed:Title' ) );
		$mock->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 42 ) );

		return $mock;
	}

	/**
	 * @param array $params
	 * @param TermSearchResult[] $matches
	 *
	 * @return EntitySearchTermIndex
	 */
	private function getMockEntitySearchHelper( array $params, array $matches ) {
		$mock = $this->getMockBuilder( EntitySearchTermIndex::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->atLeastOnce() )
			->method( 'getRankedSearchResults' )
			->with(
				$this->equalTo( $params['wbssearch'] ),
				$this->equalTo( $params['wbslanguage'] ),
				$this->equalTo( $params['wbstype'] ),
				$this->equalTo( $params['wbslimit'] ),
				$this->equalTo( false )
			)
			->will( $this->returnValue( $matches ) );

		return $mock;
	}

	/**
	 * @param array[] $expected
	 *
	 * @return ApiPageSet
	 */
	private function getMockApiPageSet( array $expected ) {
		$mock = $this->getMockBuilder( ApiPageSet::class )
			->disableOriginalConstructor()
			->getMock();

		$i = 0;
		foreach ( $expected as $entry ) {
			$mock->expects( $this->at( $i++ ) )
				->method( 'setGeneratorData' )
				->with(
					$this->equalTo( $this->getMockTitle() ),
					$this->equalTo( [ 'displaytext' => $entry['displaytext'] ] )
				);
		}

		$mock->expects( $this->once() )
			->method( 'populateFromTitles' );

		return $mock;
	}

	private function callApi( array $params, array $matches, ApiPageSet $resultPageSet = null ) {
		// defaults from SearchEntities
		$params = array_merge( [
			'wbstype' => 'item',
			'wbslimit' => 7,
			'wbslanguage' => 'en'
		], $params );

		$api = new QuerySearchEntities(
			$this->getApiQuery( $params ),
			'wbsearch',
			$this->getMockEntitySearchHelper( $params, $matches ),
			$this->getMockTitleLookup(),
			$this->getContentLanguages(),
			[ 'item', 'property' ]
		);

		$continuationManager = new ApiContinuationManager( $api, [ $api ] );

		$api->setContinuationManager( $continuationManager );

		if ( $resultPageSet !== null ) {
			$api->executeGenerator( $resultPageSet );
			return null;
		}

		$api->execute();

		$result = $api->getResult();
		$continuationManager->setContinuationIntoResult( $result );
		return $result->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );
	}

	public function provideTestQuerySearchEntities() {
		$q111Match = new TermSearchResult(
			new Term( 'qid', 'Q111' ),
			'entityId',
			new ItemId( 'Q111' )
		);

		$q222Match = new TermSearchResult(
			new Term( 'en-gb', 'Fooooo' ),
			'label',
			new ItemId( 'Q222' )
		);

		$q333Match = new TermSearchResult(
			new Term( 'de', 'AMatchedTerm' ),
			'alias',
			new ItemId( 'Q333' )
		);

		$q111Result = [
			'ns' => 0,
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'displaytext' => 'Q111'
		];

		$q222Result = [
			'ns' => 0,
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'displaytext' => 'Fooooo'
		];

		$q333Result = [
			'ns' => 0,
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'displaytext' => 'AMatchedTerm'
		];

		return [
			'No match' => [
				[ 'wbssearch' => 'Foo' ],
				[],
				[],
			],
			'Exact EntityId match' => [
				[ 'wbssearch' => 'Q111' ],
				[ $q111Match ],
				[ $q111Result ],
			],
			'Multiple Results' => [
				[ 'wbssearch' => 'Foo' ],
				[ $q222Match, $q333Match ],
				[ $q222Result, $q333Result ],
			],
			'Multiple Results (limited)' => [
				[ 'wbssearch' => 'Foo', 'wbslimit' => 1 ],
				[ $q222Match ],
				[ $q222Result ],
			],
		];
	}

	/**
	 * @dataProvider provideTestQuerySearchEntities
	 */
	public function testExecute( array $params, array $matches, array $expected ) {
		$result = $this->callApi( $params, $matches );

		$this->assertResultLooksGood( $result );
		$this->assertEquals( $expected, $result['query']['wbsearch'] );

		if ( count( $matches ) === count( $expected ) ) {
			$this->assertArrayHasKey( 'batchcomplete', $result );
		}
	}

	private function assertResultLooksGood( array $result ) {
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'wbsearch', $result['query'] );

		foreach ( $result['query']['wbsearch'] as $key => $searchresult ) {
			$this->assertInternalType( 'integer', $key );
			$this->assertArrayHasKey( 'ns', $searchresult );
			$this->assertArrayHasKey( 'title', $searchresult );
			$this->assertArrayHasKey( 'pageid', $searchresult );
			$this->assertArrayHasKey( 'displaytext', $searchresult );
		}
	}

	/**
	 * @dataProvider provideTestQuerySearchEntities
	 */
	public function testExecuteGenerator( array $params, array $matches, array $expected ) {
		$resultPageSet = $this->getMockApiPageSet( $expected );
		$this->callApi( $params, $matches, $resultPageSet );
	}

}

<?php

namespace Wikibase\Test\Repo\Api;

use ApiContinuationManager;
use ApiMain;
use ApiPageSet;
use ApiQuery;
use FauxRequest;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\QuerySearchEntities;

/**
 * @covers Wikibase\Repo\Api\QuerySearchEntities
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
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
	 * @return EntityTitleLookup|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockTitleLookup() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$titleLookup->expects( $this->any() )->method( 'getTitleForId' )
			->will( $this->returnValue( $this->getMockTitle() ) );

		return $titleLookup;
	}

	/**
	 * @return Title|\PHPUnit_Framework_MockObject_MockObject
	 */
	public function getMockTitle() {
		$mock = $this->getMockBuilder( '\Title' )
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
	 * @return EntitySearchHelper|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockEntitySearchHelper( array $params, array $matches ) {
		$mock = $this->getMockBuilder( 'Wikibase\Repo\Api\EntitySearchHelper' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->atLeastOnce() )
			->method( 'getRankedSearchResults' )
			->with(
				$this->equalTo( $params['wbssearch'] ),
				$this->equalTo( '' ),
				$this->equalTo( $params['wbstype'] ),
				$this->equalTo( $params['wbsoffset'] + $params['wbslimit'] + 1 ),
				$this->equalTo( false )
			)
			->will( $this->returnValue( $matches ) );

		return $mock;
	}

	/**
	 * @param array $matches
	 *
	 * @return ApiPageSet|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockApiPageSet( array $expected ) {
		$mock = $this->getMockBuilder( '\ApiPageSet' )
			->disableOriginalConstructor()
			->getMock();

		$i = 0;
		foreach ( $expected as $entry ) {
			$mock->expects( $this->at( $i++ ) )
				->method( 'setGeneratorData' )
				->with(
					$this->equalTo( $this->getMockTitle() ),
					$this->equalTo( array( 'displaytext' => $entry['displaytext'] ) )
				);
		}

		$mock->expects( $this->once() )
			->method( 'populateFromTitles' );

		return $mock;
	}

	private function callApi( array $params, array $matches, ApiPageSet $resultPageSet = null ) {
		// defaults from SearchEntities
		$params = array_merge( array(
			'wbstype' => 'item',
			'wbslimit' => 7,
			'wbsoffset' => 0
		), $params );

		$api = new QuerySearchEntities(
			$this->getApiQuery( $params ),
			'wbsearch'
		);

		$continuationManager = new ApiContinuationManager( $api, array( $api ) );

		$api->setContinuationManager( $continuationManager );
		$api->setServices(
			$this->getMockEntitySearchHelper( $params, $matches ),
			$this->getMockTitleLookup(),
			array( 'item', 'property' )
		);

		if ( $resultPageSet !== null ) {
			$api->executeGenerator( $resultPageSet );
			return null;
		}

		$api->execute();

		$result = $api->getResult();
		$continuationManager->setContinuationIntoResult( $result );
		return $result->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
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

		$q111Result = array(
			'ns' => 0,
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'displaytext' => 'Q111'
		);

		$q222Result = array(
			'ns' => 0,
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'displaytext' => 'Fooooo'
		);

		$q333Result = array(
			'ns' => 0,
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'displaytext' => 'AMatchedTerm'
		);

		return array(
			'No match' => array(
				array( 'wbssearch' => 'Foo' ),
				array(),
				array(),
			),
			'Exact EntityId match' => array(
				array( 'wbssearch' => 'Q111' ),
				array( $q111Match ),
				array( $q111Result ),
			),
			'Multiple Results' => array(
				array( 'wbssearch' => 'Foo' ),
				array( $q222Match, $q333Match ),
				array( $q222Result, $q333Result ),
			),
			'Multiple Results (limited)' => array(
				array( 'wbssearch' => 'Foo', 'wbslimit' => 1 ),
				array( $q222Match, $q333Match ),
				array( $q222Result ),
				1
			),
			'Multiple Results (limited-continue)' => array(
				array( 'wbssearch' => 'Foo', 'wbslimit' => 1, 'wbsoffset' => 1 ),
				array( $q222Match, $q333Match, $q111Match ),
				array( $q333Result ),
				2
			),
		);
	}

	/**
	 * @dataProvider provideTestQuerySearchEntities
	 */
	public function testExecute( array $params, array $matches, array $expected, $offset = 0 ) {
		$result = $this->callApi( $params, $matches );

		$this->assertResultLooksGood( $result );
		$this->assertEquals( $expected, $result['query']['wbsearch'] );

		if ( count( $matches ) === count( $expected ) ) {
			$this->assertArrayHasKey( 'batchcomplete', $result );
		}

		if ( $offset > 0 ) {
			$this->assertEquals( $offset, $result['continue']['wbsoffset'] );
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

<?php

namespace Wikibase\Repo\Tests\Api;

use ApiContinuationManager;
use ApiMain;
use ApiPageSet;
use ApiQuery;
use FauxRequest;
use MediaWikiIntegrationTestCase;
use RequestContext;
use Status;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\Api\EntityTermSearchHelper;
use Wikibase\Repo\Api\QuerySearchEntities;

/**
 * @covers \Wikibase\Repo\Api\QuerySearchEntities
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class QuerySearchEntitiesTest extends MediaWikiIntegrationTestCase {

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
		return $main->getModuleManager()->getModule( 'query' );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockTitleLookup() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->method( 'getTitleForId' )
			->willReturn( $this->getMockTitle() );

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
		$mock = $this->createMock( Title::class );
		$mock->method( 'getNamespace' )
			->willReturn( 0 );
		$mock->method( 'getPrefixedText' )
			->willReturn( 'Prefixed:Title' );
		$mock->method( 'getArticleID' )
			->willReturn( 42 );

		return $mock;
	}

	/**
	 * @param array $params
	 * @param TermSearchResult[] $matches
	 * @param Status|null $failureStatus
	 * @return EntityTermSearchHelper
	 */
	private function getMockEntitySearchHelper( array $params, array $matches, ?Status $failureStatus ): EntityTermSearchHelper {
		$mock = $this->createMock( EntityTermSearchHelper::class );
		$invocation = $mock->expects( $this->atLeastOnce() )
			->method( 'getRankedSearchResults' )
			->with(
				$params['wbssearch'],
				$params['wbslanguage'],
				$params['wbstype'],
				$params['wbslimit'],
				false,
				null
			);
		if ( $failureStatus !== null ) {
			$invocation->willThrowException( new EntitySearchException( $failureStatus ) );
		} else {
			$invocation->willReturn( $matches );
		}

		return $mock;
	}

	/**
	 * @param array[] $expected
	 *
	 * @return ApiPageSet
	 */
	private function getMockApiPageSet( array $expected ) {
		$mock = $this->createMock( ApiPageSet::class );

		$expectedParams = [];
		foreach ( $expected as $entry ) {
			$expectedParams[] = [
				$this->getMockTitle(),
				[ 'displaytext' => $entry['displaytext'] ],
			];
		}
		$mock
			->method( 'setGeneratorData' )
			->withConsecutive( ...$expectedParams );

		$mock->expects( $this->once() )
			->method( 'populateFromTitles' );

		return $mock;
	}

	private function callApi( array $params, array $matches, ApiPageSet $resultPageSet = null, Status $failureStatus = null ) {
		// defaults from SearchEntities
		$params = array_merge( [
			'wbstype' => 'item',
			'wbslimit' => 7,
			'wbslanguage' => 'en',
		], $params );

		$api = new QuerySearchEntities(
			$this->getApiQuery( $params ),
			'wbsearch',
			$this->getMockEntitySearchHelper( $params, $matches, $failureStatus ),
			$this->getMockTitleLookup(),
			$this->getContentLanguages(),
			[ 'item', 'property' ],
			[ 'default' => null, 'unused' => 'unused-internal' ]
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
			'displaytext' => 'Q111',
		];

		$q222Result = [
			'ns' => 0,
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'displaytext' => 'Fooooo',
		];

		$q333Result = [
			'ns' => 0,
			'title' => 'Prefixed:Title',
			'pageid' => 42,
			'displaytext' => 'AMatchedTerm',
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

	public function testSearchBackendErrorIsPropagatedDuringExecute() {
		$errorStatus = Status::newFatal( 'search-backend-error' );
		try {
			$this->callApi( [ 'wbssearch' => 'Foo' ], [], null, $errorStatus );
			$this->fail( "Exception must be thrown" );
		} catch ( \ApiUsageException $aue ) {
			$this->assertSame( $errorStatus, $aue->getStatusValue() );
		}
	}

	private function assertResultLooksGood( array $result ) {
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'wbsearch', $result['query'] );

		foreach ( $result['query']['wbsearch'] as $key => $searchresult ) {
			$this->assertIsInt( $key );
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

	public function testSearchBackendErrorIsPropagatedDuringExecuteGenerator() {
		$errorStatus = Status::newFatal( 'search-backend-error' );
		$mock = $this->createMock( ApiPageSet::class );
		try {
			$this->callApi( [ 'wbssearch' => 'Foo' ], [], $mock, $errorStatus );
			$this->fail( "Exception must be thrown" );
		} catch ( \ApiUsageException $aue ) {
			$this->assertSame( $errorStatus, $aue->getStatusValue() );
		}
	}

}

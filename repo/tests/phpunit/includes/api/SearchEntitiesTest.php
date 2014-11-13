<?php

namespace Wikibase\Test\Api;

/**
 * @covers Wikibase\Api\SearchEntities
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class SearchEntitiesTest extends WikibaseApiTestCase {

	private static $hasSetup;

	protected function setUp() {
		parent::setUp();

		if( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( array( 'StringProp', 'Berlin', 'London', 'Oslo', 'Episkopi',
				'Leipzig', 'Guangzhou', 'Osaka' )
			);
		}
		self::$hasSetup = true;
	}

	public function provideData() {
		$testCases = array();

		//Search via full Labels
		$testCases[] = array( array( 'search' => 'berlin', 'language' => 'en' ), array( 'handle' => 'Berlin' ) );
		$testCases[] = array( array( 'search' => 'LoNdOn', 'language' => 'en' ), array( 'handle' => 'London' ) );
		$testCases[] = array( array( 'search' => 'OSLO', 'language' => 'en' ), array( 'handle' => 'Oslo' ) );
		$testCases[] = array( array( 'search' => '广州市', 'language' => 'zh-cn' ), array( 'handle' => 'Guangzhou' ) );

		//Search via partial Labels
		$testCases[] = array( array( 'search' => 'BER', 'language' => 'nn' ), array( 'handle' => 'Berlin' ) );
		$testCases[] = array( array( 'search' => 'Episkop', 'language' => 'de' ), array( 'handle' => 'Episkopi' ) );
		$testCases[] = array( array( 'search' => 'L', 'language' => 'de' ), array( 'handle' => 'Leipzig' ) );

		return $testCases;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSearchEntities( $params, $expected ) {
		$params['action'] = 'wbsearchentities';

		list( $result,, ) = $this->doApiRequest( $params );

		$this->assertResultLooksGood( $result );
		$this->assertApiResultHasExpected( $result['search'], $params, $expected );
	}

	public function testSearchExactMatch() {
		$params = array(
			'action' => 'wbsearchentities',
			'search' => EntityTestHelper::getId( 'Berlin' ),
			'language' => 'en'
		);

		$expected = array( 'handle' => 'Berlin' );

		list( $result,, ) = $this->doApiRequest( $params );
		$this->assertApiResultHasExpected( $result['search'], $params, $expected );
	}

	public function testSearchContinue() {
		$params = array(
			'action' => 'wbsearchentities',
			'search' => 'O',
			'language' => 'en',
			'limit' => 1
		);

		list( $result,, ) = $this->doApiRequest( $params );

		$this->assertArrayHasKey( 'search-continue', $result );
	}

	private function assertResultLooksGood( $result ) {
		$this->assertResultSuccess( $result );
		$this->assertArrayHasKey( 'searchinfo', $result );
		$this->assertArrayHasKey( 'search', $result['searchinfo'] );
		$this->assertArrayHasKey( 'search', $result );

		foreach( $result['search'] as $key => $searchresult ) {
			$this->assertInternalType( 'integer', $key );
			$this->assertArrayHasKey( 'id', $searchresult );
			$this->assertArrayHasKey( 'url', $searchresult );
		}

	}

	private function assertApiResultHasExpected( $searchResults, $params, $expected ) {
		$foundResult = 0;

		$expectedId = EntityTestHelper::getId( $expected['handle'] );
		$expectedData = EntityTestHelper::getEntityData( $expected['handle'] );

		foreach( $searchResults as $searchResult ) {
			$assertFound = $this->assertSearchResultHasExpected( $searchResult, $params, $expectedId, $expectedData );
			$foundResult = $foundResult + $assertFound;
		}
		$this->assertEquals( 1, $foundResult, 'Could not find expected search result in array of results' );
	}

	private function assertSearchResultHasExpected( $searchResult, $params, $expectedId, $expectedData  ){
		if( $expectedId === $searchResult['id'] ) {
			$this->assertEquals( $expectedId, $searchResult['id'] );
			$this->assertStringEndsWith( $expectedId, $searchResult['url'] );
			if( array_key_exists( 'descriptions', $expectedData ) ) {
				$this->assertSearchResultHasExpectedDescription( $searchResult, $params, $expectedData );
			}
			if( array_key_exists( 'labels', $expectedData ) ) {
				$this->assertSearchResultHasExpectedLabel( $searchResult, $params, $expectedData );
			}
			return 1;
		}
		return 0;
	}

	private function assertSearchResultHasExpectedDescription( $searchResult, $params, $expectedData ) {
		foreach( $expectedData['descriptions'] as $description ) {
			if( $description['language'] == $params['language'] ) {
				$this->assertArrayHasKey( 'description', $searchResult );
				$this->assertEquals( $description['value'], $searchResult['description'] );
			}
		}
	}

	private function assertSearchResultHasExpectedLabel( $searchResult, $params, $expectedData ) {
		foreach( $expectedData['labels'] as $description ) {
			if( $description['language'] == $params['language'] ) {
				$this->assertArrayHasKey( 'label', $searchResult );
				$this->assertEquals( $description['value'], $searchResult['label'] );
			}
		}
	}
}

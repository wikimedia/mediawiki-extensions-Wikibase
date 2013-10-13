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

	public function setUp() {
		parent::setUp();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Berlin', 'London', 'Oslo', 'Episkopi', 'Leipzig', 'Guangzhou' ) );
		}
		self::$hasSetup = true;
	}

	public function provideData(){
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
	public function testSearchEntities( $params, $expected ){
		$params['action'] = 'wbsearchentities';

		list( $result,, ) = $this->doApiRequest( $params );

		$this->assertResultLooksGood( $result );
		$this->assertResultHasExpected( $result['search'], $expected );
	}

	private function assertResultLooksGood( $result ) {
		$this->assertResultSuccess( $result );
		$this->assertArrayHasKey( 'searchinfo', $result );
		$this->assertArrayHasKey( 'search', $result['searchinfo'] );
		$this->assertArrayHasKey( 'search', $result );

		foreach( $result['search'] as $key => $searchresult ){
			$this->assertInternalType( 'integer', $key );
			$this->assertArrayHasKey( 'id', $searchresult );
			$this->assertArrayHasKey( 'url', $searchresult );
			//TODO assert the label and description are returned if defined
			//$this->assertArrayHasKey( 'description', $searchresult );
			//$this->assertArrayHasKey( 'label', $searchresult );
		}

	}

	private function assertResultHasExpected( $searchResults, $expected ){
		$foundResult = false;

		$expectedId = EntityTestHelper::getId( $expected['handle'] );

		foreach( $searchResults as $searchResult ){
			if( $expectedId === $searchResult['id'] &&
				strstr( $searchResult['url'], $expectedId )
				//Todo assert correct description
				//Todo assert correct label
			){
				$foundResult = true;
			}
		}
		$this->assertTrue( $foundResult, 'Could not find expected search result in array of results' );
	}
}
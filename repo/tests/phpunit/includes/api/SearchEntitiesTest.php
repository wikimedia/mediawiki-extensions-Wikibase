<?php

namespace Wikibase\Test\Api;

use ApiMain;
use FauxRequest;
use PHPUnit_Framework_TestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Term;
use Wikibase\TermIndex;
use Wikibase\Api\SearchEntities;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Test\MockTermIndex;

/**
 * @covers Wikibase\Api\SearchEntities
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Daniel Kinzler
 */
class SearchEntitiesTest extends PHPUnit_Framework_TestCase {

	private static $terms = array(
		'Berlin' => array(
			'id' => 'Q64',
			'labels' => array(
				array( 'language' => 'en', 'value' => 'Berlin' ),
				array( 'language' => 'de', 'value' => 'Berlin' ),
			),
			'aliases' => array(
				array( array( 'language' => 'de', 'value' => 'Dickes B' ) ),
				array( array( 'language' => 'en', 'value' => 'Dickes B' ) ),
			),
			'descriptions' => array(
				array( 'language' => 'en', 'value' => 'Capital city and a federated state of the Federal Republic of Germany.' ),
				array( 'language' => 'de', 'value' => 'Bundeshauptstadt und Regierungssitz der Bundesrepublik Deutschland.' ),
			),
		),
		'Bern' => array(
			'id' => 'Q45',
			'labels' => array(
				array( 'language' => 'en', 'value' => 'Bern' ),
				array( 'language' => 'de', 'value' => 'Bern' ),
				array( 'language' => 'fr', 'value' => 'Berne' ),
				array( 'language' => 'it', 'value' => 'Berna' ),
			),
			'aliases' => array(
			),
			'descriptions' => array(
				array( 'language' => 'de', 'value' => 'Stadt in der Schweiz.' ),
			),
		),
		'Guangzhou' => array(
			'id' => 'Q231',
			'labels' => array(
				array( 'language' => 'en', 'value' => 'Guangzhou' ),
				array( 'language' => 'yue', 'value' => '廣州' ),
				array( 'language' => 'zh-cn', 'value' => '广州市' ),
			),
			'aliases' => array(
			),
			'descriptions' => array(
				array( 'language' => 'en', 'value' => 'Capital of Guangdong.' ),
				array( 'language' => 'zh-hk', 'value' => '廣東的省會。' ),
			),
		),
	);

	private function getEntityId( $handle ) {
		$parser = new BasicEntityIdParser();
		return $parser->parse( self::$terms[$handle]['id'] );
	}

	private function getEntityData( $handle ) {
		return self::$terms[$handle];
	}

	/**
	 * @param array $params
	 *
	 * @return ApiMain
	 */
	private function getApiMain( array $params ) {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, true ) );

		$main = new ApiMain( $context );
		return $main;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getTitleLookup() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$titleLookup->expects( $this->any() )->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$title = Title::makeTitle( NS_MAIN, $id->getEntityType() . ':' . $id->getSerialization() );
				$title->resetArticleID( $id->getNumericId() );
				return $title;
			} ) );

		return $titleLookup;
	}

	/**
	 * @return ContentLanguages
	 */
	private function getContentLanguages() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\ContentLanguages' );
		$titleLookup->expects( $this->any() )->method( 'getLanguages' )
			->will( $this->returnValue( array( 'de', 'de-ch', 'en', 'ii', 'nn', 'ru', 'zh-cn' ) ) );

		return $titleLookup;
	}

	/**
	 * @return TermIndex
	 */
	private function getTermIndex() {
		$idParser = new BasicEntityIdParser();
		$termObjects = array();
		foreach ( self::$terms as $entity ) {
			$id = $idParser->parse( $entity['id'] );

			foreach ( $entity['labels'] as $row ) {
				$termObjects[] = $this->newTermFromDataRow( $id, Term::TYPE_LABEL, $row );
			}

			foreach ( $entity['descriptions'] as $row ) {
				$termObjects[] = $this->newTermFromDataRow( $id, Term::TYPE_DESCRIPTION, $row );
			}

			foreach ( $entity['aliases'] as $rows ) {
				foreach ( $rows as $row ) {
					$termObjects[] = $this->newTermFromDataRow( $id, Term::TYPE_ALIAS, $row );
				}
			}
		}

		$termIndex = new MockTermIndex( $termObjects );

		return $termIndex;
	}

	private function newTermFromDataRow( EntityId $entityId, $type, $row ) {
		return new Term( array(
			'termType' => $type,
			'termLanguage' => $row['language'],
			'termText' => $row['value'],
			'entityType' => $entityId->getEntityType(),
			'entityId' => $entityId->getNumericId()
		) );
	}

	/**
	 * @param array $params
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params ) {
		$module = new SearchEntities(
			$this->getApiMain( $params ),
			'wbsearchentities'
		);

		$module->setServices(
			$this->getTermIndex(),
			$this->getTitleLookup(),
			new BasicEntityIdParser(),
			array( 'item', 'property' ),
			$this->getContentLanguages()

		);

		$module->execute();

		$result = $module->getResult();
		return $result->getData();
	}

	public function provideData() {
		$testCases = array();

		//Search via full Labels
		$testCases[] = array( array( 'search' => 'berlin', 'language' => 'en' ), array( 'handle' => 'Berlin' ) );
		$testCases[] = array( array( 'search' => 'bERliN', 'language' => 'en' ), array( 'handle' => 'Berlin' ) );
		$testCases[] = array( array( 'search' => 'BERLIN', 'language' => 'en' ), array( 'handle' => 'Berlin' ) );
		$testCases[] = array( array( 'search' => '广州市', 'language' => 'zh-cn' ), array( 'handle' => 'Guangzhou' ) );

		//Search via partial Labels
		$testCases[] = array( array( 'search' => 'BER', 'language' => 'de' ), array( 'handle' => 'Berlin' ) );
		$testCases[] = array( array( 'search' => '广', 'language' => 'zh-cn' ), array( 'handle' => 'Guangzhou' ) );

		return $testCases;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSearchEntities( $params, $expected ) {
		$params['action'] = 'wbsearchentities';

		$result = $this->callApiModule( $params );

		$this->assertResultLooksGood( $result );
		$this->assertApiResultHasExpected( $result['search'], $params, $expected );
	}

	public function testSearchExactMatch() {
		$params = array(
			'action' => 'wbsearchentities',
			'search' => $this->getEntityId( 'Berlin' ),
			'language' => 'en'
		);

		$expected = array( 'handle' => 'Berlin' );

		$result = $this->callApiModule( $params );
		$this->assertApiResultHasExpected( $result['search'], $params, $expected );
	}

	public function testSearchContinue() {
		$params = array(
			'action' => 'wbsearchentities',
			'search' => 'B',
			'language' => 'de',
			'limit' => 1
		);

		$result = $this->callApiModule( $params );

		$this->assertArrayHasKey( 'search-continue', $result );
	}

	private function assertResultLooksGood( $result ) {
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

		$expectedId = $this->getEntityId( $expected['handle'] )->getSerialization();
		$expectedData = $this->getEntityData( $expected['handle'] );

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

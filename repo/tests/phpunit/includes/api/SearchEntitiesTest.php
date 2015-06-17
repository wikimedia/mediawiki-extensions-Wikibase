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
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndexEntry;
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
				array( 'language' => 'en', 'value' => 'City in Switzerland.' ),
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
		'X1' => array(
			'id' => 'Q1001',
			'labels' => array(
				array( 'language' => 'en', 'value' => 'label:x1:en' ),
			),
			'aliases' => array(
				array( array( 'language' => 'en', 'value' => 'alias1:x1:en' ) ),
			),
			'descriptions' => array(
				array( 'language' => 'en', 'value' => 'description:x1:en' ),
			),
		),
		'X2' => array(
			'id' => 'Q1002',
			'labels' => array(
				array( 'language' => 'en', 'value' => 'label:x2:en' ),
				array( 'language' => 'de', 'value' => 'label:x2:de' ),
			),
			'aliases' => array(
				array( array( 'language' => 'en', 'value' => 'alias1:x2:en' ) ),
			),
			'descriptions' => array(
				array( 'language' => 'en', 'value' => 'description:x2:en' ),
			),
		),
		'X3' => array(
			'id' => 'Q1003',
			'labels' => array(
				array( 'language' => 'en', 'value' => 'label:x3:en' ),
				array( 'language' => 'de', 'value' => 'label:x3:de' ),
				array( 'language' => 'de-ch', 'value' => 'label:x3:de-ch' ),
			),
			'aliases' => array(
				array( array( 'language' => 'en', 'value' => 'alias1:x3:en' ) ),
				array( array( 'language' => 'en', 'value' => 'description:x3:en' ) ),
				array( array( 'language' => 'de', 'value' => 'description:x3:de' ) ),
				array( array( 'language' => 'de-ch', 'value' => 'description:x3:de-ch' ) ),
			),
			'descriptions' => array(
				array( 'language' => 'en', 'value' => 'description:x3:en' ),
				array( 'language' => 'de', 'value' => 'description:x3:de' ),
				array( 'language' => 'de-ch', 'value' => 'description:x3:de-ch' ),
			),
		),
	);

	private function getEntityId( $handle ) {
		return self::$terms[$handle]['id'];
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
				$termObjects[] = $this->newTermFromDataRow( $id, TermIndexEntry::TYPE_LABEL, $row );
			}

			foreach ( $entity['descriptions'] as $row ) {
				$termObjects[] = $this->newTermFromDataRow( $id, TermIndexEntry::TYPE_DESCRIPTION, $row );
			}

			foreach ( $entity['aliases'] as $rows ) {
				foreach ( $rows as $row ) {
					$termObjects[] = $this->newTermFromDataRow( $id, TermIndexEntry::TYPE_ALIAS, $row );
				}
			}
		}

		$termIndex = new MockTermIndex( $termObjects );

		return $termIndex;
	}

	private function newTermFromDataRow( EntityId $entityId, $type, $row ) {
		return new TermIndexEntry( array(
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
			$this->getContentLanguages(),
			WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory()
		);

		$module->execute();

		$result = $module->getResult();
		return $result->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
	}

	public function provideData() {
		return array(
			//Search via full Labels
			'en:Berlin' => array( array( 'search' => 'Berlin', 'language' => 'en' ), array( array( 'label' => 'Berlin' ) ) ),
			'en:bERliN' => array( array( 'search' => 'bERliN', 'language' => 'en' ), array( array( 'label' => 'Berlin' ) ) ),
			'zh-cn:广州市' => array( array( 'search' => '广州市', 'language' => 'zh-cn' ), array( array( 'label' => '广州市' ) ) ),

			//Search via partial Labels
			'de:Guang' => array( array( 'search' => 'Guang', 'language' => 'de' ), array( array( 'label' => 'Guangzhou' ) ) ),
			'zh-cn:广' => array( array( 'search' => '广', 'language' => 'zh-cn' ), array( array( 'label' => '广州市' ) ) ),

			//Match alias
			'de:Dickes' => array( array( 'search' => 'Dickes', 'language' => 'de' ), array( array( 'label' => 'Berlin', 'aliases' => array( 'Dickes B' ) ) ) ),

			//Multi-match language fallback
			'de:x' => array( array( 'search' => 'alias1:x', 'language' => 'de-ch' ), array(
				array( 'label' => 'label:x1:en' ),
				array( 'label' => 'label:x2:de' ),
				array( 'label' => 'label:x3:de-ch' ),
			) ),
		);
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSearchEntities( $params, $expected ) {
		$params['action'] = 'wbsearchentities';

		$result = $this->callApiModule( $params );

		$this->assertResultLooksGood( $result );
		$this->assertResultSet( $expected, $result['search'] );
	}

	public function testSearchExactMatch() {
		$params = array(
			'action' => 'wbsearchentities',
			'search' => $this->getEntityId( 'Berlin' ),
			'language' => 'en'
		);

		$expected = array( array(
			'label' => 'Berlin',
			'description' => 'Capital city and a federated state of the Federal Republic of Germany.',
		) );

		$result = $this->callApiModule( $params );
		$this->assertResultSet( $expected, $result['search'] );
	}


	public function testSearchFallback() {
		$params = array(
			'action' => 'wbsearchentities',
			'search' => 'BERN',
			'language' => 'de-ch',
		);

		$result = $this->callApiModule( $params );
		$this->assertCount( 1, $result['search'] );

		$resultEntry = reset( $result['search'] );
		$this->assertEquals( 'Bern', $resultEntry['label'] );
		$this->assertEquals( 'Stadt in der Schweiz.', $resultEntry['description'] );
	}

	public function testSearchStrictLanguage() {
		$params = array(
			'action' => 'wbsearchentities',
			'search' => 'Berlin',
			'language' => 'de-ch',
			'strictlanguage' => true
		);

		$result = $this->callApiModule( $params );
		$this->assertEmpty( $result['search'] );
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

	private function assertResultSet( $expected, $actual ) {
		reset( $actual );
		foreach ( $expected as $expectedEntry ) {
			$actualEntry = current( $actual );
			next( $actual );

			$this->assertTrue( $actualEntry !== false, 'missing result entry ' . var_export( $expectedEntry, true ) );
			$this->assertResultEntry( $expectedEntry, $actualEntry );
		}

		$actualEntry = next( $actual );
		$this->assertFalse( $actualEntry, 'extra result entry ' . var_export( $actualEntry, true ) );
	}

	private function assertResultEntry( $expected, $actual ) {
		$actual = array_intersect_key( $actual, $expected );

		ksort( $expected );
		ksort( $actual );

		$this->assertEquals( $expected, $actual );
	}

}

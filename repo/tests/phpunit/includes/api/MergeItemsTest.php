<?php

namespace Wikibase\Test\Api;

use Wikibase\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Property;
use Wikibase\PropertyContent;

/**
 * Unit tests for the Wikibase\Repo\Api\MergeItems class.
 *
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group MergeItemsTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class MergeItemsTest extends WikibaseApiTestCase {

	//todo add check merge conflicts are thrown

	private static $hasSetup;

	public function setUp() {
		parent::setUp();
		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Empty', 'Empty2' ) );
			$prop42 = PropertyId::newFromNumber( 56 );
			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( $prop42 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );
		}
		self::$hasSetup = true;
	}

	public static function provideData(){
		return array(
			//check all elements move individually
			array(
				array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
				array(),
				array(),
				array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			),
			array(
				array( 'descriptions' => array( 'de' => array( 'language' => 'de', 'value' => 'foo' ) ) ),
				array(),
				array(),
				array( 'descriptions' => array( 'de' => array( 'language' => 'de', 'value' => 'foo' ) ) ),
			),
			array(
				array( 'aliases' => array( array( "language" => "nl", "value" => "Dickes B" ) ) ),
				array(),
				array(),
				array( 'aliases' => array( array( "language" => "nl", "value" => "Dickes B" ) ) ),
			),
			array(
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
				array(),
				array(),
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
			),
			array(
				array( 'claims' => array( 'P56' => array( 'mainsnak' => array(
					'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
					'type' => 'statement', 'rank' => 'normal' ) ) ),
				array(),
				array(),
				array( 'claims' => array( array( 'mainsnak' => array(
					'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
					'type' => 'statement', 'rank' => 'normal' ) ) ),
			),
			//check merges of elements work as expected
			array(
				array( 'aliases' => array( array( "language" => "nl", "value" => "Ali1" ) ) ),
				array( 'aliases' => array( array( "language" => "nl", "value" => "Ali2" ) ) ),
				array(),
				array( 'aliases' => array( array( "language" => "nl", "value" => "Ali2" ),array( "language" => "nl", "value" => "Ali1" ) ) ),
			),
			array(
				array( 'claims' => array( 'P56' => array( 'mainsnak' => array(
					'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring1', 'type' => 'string' ) ),
					'type' => 'statement', 'rank' => 'normal' ) ) ),
				array( 'claims' => array( 'P56' => array( 'mainsnak' => array(
					'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring2', 'type' => 'string' ) ),
					'type' => 'statement', 'rank' => 'normal' ) ) ),
				array(),
				array( 'claims' => array(
					array( 'mainsnak' => array( 'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring2', 'type' => 'string' ) ), 'type' => 'statement', 'rank' => 'normal' ),
					array( 'mainsnak' => array( 'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring1', 'type' => 'string' ) ), 'type' => 'statement', 'rank' => 'normal' ) ) ),
			),
			array(
				array( 'claims' => array( 'P56' => array( 'mainsnak' => array(
					'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
					'type' => 'statement', 'rank' => 'normal' ) ) ),
				array( 'claims' => array( 'P56' => array( 'mainsnak' => array(
					'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
					'type' => 'statement', 'rank' => 'normal' ) ) ),
				array(),
				array( 'claims' => array(
					array( 'mainsnak' => array( 'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ), 'type' => 'statement', 'rank' => 'normal' ),
					array( 'mainsnak' => array( 'snaktype' => 'value', 'property' => 'P56', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ), 'type' => 'statement', 'rank' => 'normal' ) ) ),
			),
		);
	}

	/**
	 * @dataProvider provideData
	 */
	function testMergeRequest( $pre1, $pre2, $expected1, $expected2 ){
		// -- prefill the entities --------------------------------------------
		$this->doApiRequestWithToken( array( 'action' => 'wbeditentity', 'id' => EntityTestHelper::getId( 'Empty' ) ,'clear' => '', 'data' => json_encode( $pre1 ) ) );
		$this->doApiRequestWithToken( array( 'action' => 'wbeditentity', 'id' => EntityTestHelper::getId( 'Empty2' ) ,'clear' => '', 'data' => json_encode( $pre2 ) ) );

		// -- do the request --------------------------------------------
		list( $result,, ) = $this->doApiRequestWithToken( array(
			'action' => 'wbmergeitems',
			'fromid' => EntityTestHelper::getId( 'Empty' ),
			'toid' => EntityTestHelper::getId( 'Empty2' ),
			'summary' => 'CustomSummary!',
		) );

		// -- check the result --------------------------------------------
		$this->assertResultSuccess( $result );
		$this->assertArrayHasKey( 'from', $result );
		$this->assertArrayHasKey( 'to', $result );
		$this->assertArrayHasKey( 'id', $result['from'] );
		$this->assertArrayHasKey( 'id', $result['to'] );
		$this->assertArrayHasKey( 'lastrevid', $result['from'] );
		$this->assertArrayHasKey( 'lastrevid', $result['to'] );
		$this->assertGreaterThan( 0, $result['from']['lastrevid'] );
		$this->assertGreaterThan( 0, $result['to']['lastrevid'] );

		// -- check the items --------------------------------------------
		$this->assertEntityEquals( $expected1, $this->loadEntity( $result['from']['id'] ) );
		$this->assertEntityEquals( $expected2, $this->loadEntity( $result['to']['id'] ) );

		// -- check the edit summaries --------------------------------------------
		$this->assertRevisionSummary( array( 'wbmergeitems' ), $result['from']['lastrevid'] );
		$this->assertRevisionSummary( "/CustomSummary/" , $result['from']['lastrevid'] );
		$this->assertRevisionSummary( array( 'wbmergeitems' ), $result['to']['lastrevid'] );
		$this->assertRevisionSummary( "/CustomSummary/" , $result['to']['lastrevid'] );
	}

	public static function provideExceptionParamsData() {
		return array(
			array( //0 no ids given
				'p' => array( ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //1 only from id
				'p' => array( 'fromid' => 'q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //2 only to id
				'p' => array( 'toid' => 'q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //3 toid bad
				'p' => array( 'fromid' => 'q1', 'toid' => 'ABCDE' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
			array( //4 fromid bad
				'p' => array( 'fromid' => 'ABCDE', 'toid' => 'q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
			array( //5 bot same id
				'p' => array( 'fromid' => 'q1', 'toid' => 'q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-invalid' ) ) ),
			array( //6 from id is property
				'p' => array( 'fromid' => 'p56', 'toid' => 'q2' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-item' ) ) ),
			array( //7 to id is property
				'p' => array( 'fromid' => 'q2', 'toid' => 'p56' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-item' ) ) ),
		);
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testMergeItemsParamsExceptions( $params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbmergeitems';
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

	public static function provideExceptionConflictsData() {
		return array(
			array(
				array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
				array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo2' ) ) ),
			),
			array(
				array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
				array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'foo2' ) ) ),
			),
			array(
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo2' ) ) ),
			),
		);
	}

	/**
	 * @dataProvider provideExceptionConflictsData
	 */
	public function testMergeItemsConflictsExceptions( $pre1, $pre2 ){
		$expected = array( 'exception' => array( 'type' => 'UsageException', 'code' => 'merge-conflict' ) );

		// -- prefill the entities --------------------------------------------
		$this->doApiRequestWithToken( array( 'action' => 'wbeditentity', 'id' => EntityTestHelper::getId( 'Empty' ) ,'clear' => '', 'data' => json_encode( $pre1 ) ) );
		$this->doApiRequestWithToken( array( 'action' => 'wbeditentity', 'id' => EntityTestHelper::getId( 'Empty2' ) ,'clear' => '', 'data' => json_encode( $pre2 ) ) );

		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => EntityTestHelper::getId( 'Empty' ),
			'toid' => EntityTestHelper::getId( 'Empty2' ),
		);

		// -- do the request --------------------------------------------
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

}

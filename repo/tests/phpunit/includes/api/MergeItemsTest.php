<?php

namespace Wikibase\Test\Api;

use LogicException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\MergeItems
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

	private static $hasSetup = false;

	/**
	 * @var EntityId
	 */
	private static $thePropertyId;

	/**
	 * @var EntityId
	 */
	private static $theItemId;

	public function setUp() {
		parent::setUp();

		if( !self::$hasSetup ){
			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

			$this->initTestEntities( array( 'Empty', 'Empty2' ) );

			$prop = Property::newFromType( 'string' );
			$store->saveEntity( $prop, 'mergeitemstest', $GLOBALS['wgUser'], EDIT_NEW );

			self::$thePropertyId = $prop->getId();

			$item = Item::newEmpty();
			$store->saveEntity( $item, 'mergeitemstest', $GLOBALS['wgUser'], EDIT_NEW );

			self::$theItemId = $item->getId();

			self::$hasSetup = true;
		}
	}

	public static function provideData(){
		$testCases = array();
		$testCases['labelMerge'] = array(
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			array(),
			array(),
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
		);
		$testCases['identicalLabelMerge'] = array(
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			array(),
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
		);
		$testCases['ignoreConflictLabelMerge'] = array(
			array( 'labels' => array(
				'en' => array( 'language' => 'en', 'value' => 'foo' ),
				'de' => array( 'language' => 'de', 'value' => 'berlin' )
			) ),
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'bar' ) ) ),
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			array( 'labels' => array(
				'en' => array( 'language' => 'en', 'value' => 'bar' ),
				'de' => array( 'language' => 'de', 'value' => 'berlin' )
			) ),
			'label'
		);
		$testCases['descriptionMerge'] = array(
			array( 'descriptions' => array( 'de' => array( 'language' => 'de', 'value' => 'foo' ) ) ),
			array(),
			array(),
			array( 'descriptions' => array( 'de' => array( 'language' => 'de', 'value' => 'foo' ) ) ),
		);
		$testCases['identicalDescriptionMerge'] = array(
			array( 'descriptions' => array( 'de' => array( 'language' => 'de', 'value' => 'foo' ) ) ),
			array( 'descriptions' => array( 'de' => array( 'language' => 'de', 'value' => 'foo' ) ) ),
			array(),
			array( 'descriptions' => array( 'de' => array( 'language' => 'de', 'value' => 'foo' ) ) ),
		);
		$testCases['ignoreConflictDescriptionMerge'] = array(
			array( 'descriptions' => array(
				'en' => array( 'language' => 'en', 'value' => 'foo' ),
				'de' => array( 'language' => 'de', 'value' => 'berlin' )
			) ),
			array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'bar' ) ) ),
			array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			array( 'descriptions' => array(
				'en' => array( 'language' => 'en', 'value' => 'bar' ),
				'de' => array( 'language' => 'de', 'value' => 'berlin' )
			) ),
			'description'
		);
		$testCases['aliasesMerge'] = array(
			array( 'aliases' => array( array( "language" => "nl", "value" => "Dickes B" ) ) ),
			array(),
			array(),
			array( 'aliases' => array( array( "language" => "nl", "value" => "Dickes B" ) ) ),
		);
		$testCases['aliasesMerge2'] = array(
			array( 'aliases' => array( array( "language" => "nl", "value" => "Ali1" ) ) ),
			array( 'aliases' => array( array( "language" => "nl", "value" => "Ali2" ) ) ),
			array(),
			array( 'aliases' => array( array( "language" => "nl", "value" => "Ali2" ),array( "language" => "nl", "value" => "Ali1" ) ) ),
		);
		$testCases['sitelinksMerge'] = array(
			array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
			array(),
			array(),
			array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
		);
		$testCases['IgnoreConflictSitelinksMerge'] = array(
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainFrom' ),
				'enwiki' => array( 'site' => 'enwiki', 'title' => 'PlFrom' ),
			) ),
			array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainTo' ) ) ),
			array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainFrom' ) ) ),
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainTo' ),
				'enwiki' => array( 'site' => 'enwiki', 'title' => 'PlFrom' ),
			) ),
			'sitelink'
		);
		$testCases['claimMerge'] = array(
			array( 'claims' => array( '{Prop}' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => '{Prop}', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal' ) ) ) ),
			array(),
			array(),
			array( 'claims' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => '{Prop}', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal' ) ) ),
		);
		$testCases['claimMerge'] = array(
			array( 'claims' => array( '{Prop}' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => '{Prop}', 'datavalue' => array( 'value' => 'imastring1', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal' ) ) ) ),
			array( 'claims' => array( '{Prop}' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => '{Prop}', 'datavalue' => array( 'value' => 'imastring2', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal' ) ) ) ),
			array(),
			array( 'claims' => array(
				array( 'mainsnak' => array( 'snaktype' => 'value', 'property' => '{Prop}', 'datavalue' => array( 'value' => 'imastring2', 'type' => 'string' ) ), 'type' => 'statement', 'rank' => 'normal' ),
				array( 'mainsnak' => array( 'snaktype' => 'value', 'property' => '{Prop}', 'datavalue' => array( 'value' => 'imastring1', 'type' => 'string' ) ), 'type' => 'statement', 'rank' => 'normal' ) ) ),
		);
		//Identical claims (mainsnak and qualifiers) should merge references
		$testCases['identicalClaimMergeReferences'] = array(
			array( 'claims' =>
				array( '{Prop}' =>
					array( array( 'mainsnak' =>
						array( 'snaktype' => 'value', 'property' => '{Prop}', 'datavalue' =>
							array( 'value' => 'imastring', 'type' => 'string' ) ),
						'type' => 'statement',
						'rank' => 'normal',
						'references' => array(
							array( 'snaks' => array( '{Prop}' => array(
								array( 'snaktype' => 'value',
									'property' => '{Prop}',
									'datavalue' => array(
										'value' => 'imastring',
										'type' => 'string'
									)
								) ) ) ) ) ) ) ) ),
			array( 'claims' =>
				array( '{Prop}' =>
					array( array( 'mainsnak' =>
						array( 'snaktype' => 'value', 'property' => '{Prop}', 'datavalue' =>
							array( 'value' => 'imastring', 'type' => 'string' ) ),
						'type' => 'statement',
						'rank' => 'normal'
					) ) ) ),
			array(), //empty
			array( 'claims' =>
				array( '{Prop}' =>
					array( array( 'mainsnak' =>
						array( 'snaktype' => 'value', 'property' => '{Prop}', 'datavalue' =>
							array( 'value' => 'imastring', 'type' => 'string' ) ),
						'type' => 'statement',
						'rank' => 'normal',
						'references' => array(
							array( 'snaks' => array( '{Prop}' => array(
								array( 'snaktype' => 'value',
									'property' => '{Prop}',
									'datavalue' => array(
										'value' => 'imastring',
										'type' => 'string',
										'snaks-order' => '{Prop}'
									)
								) ) ) ) ) ) ) ) ),
		);
		return $testCases;
	}

	/**
	 * @dataProvider provideData
	 */
	function testMergeRequest( $pre1, $pre2, $expectedFrom, $expectedTo, $ignoreConflicts = null ){
		$this->injectIds( $pre1 );
		$this->injectIds( $pre2 );
		$this->injectIds( $expectedFrom );
		$this->injectIds( $expectedTo );

		// -- set up params ---------------------------------
		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => EntityTestHelper::getId( 'Empty' ),
			'toid' => EntityTestHelper::getId( 'Empty2' ),
			'summary' => 'CustomSummary!',
		);
		if( $ignoreConflicts !== null ){
			$params['ignoreconflicts'] = $ignoreConflicts;
		}
		// -- prefill the entities --------------------------------------------
		$this->doApiRequestWithToken( array(
			'action' => 'wbeditentity',
			'id' => EntityTestHelper::getId( 'Empty' ) ,
			'clear' => '',
			'data' => json_encode( $pre1 ) ) );
		$this->doApiRequestWithToken( array(
			'action' => 'wbeditentity',
			'id' => EntityTestHelper::getId( 'Empty2' ) ,
			'clear' => '',
			'data' => json_encode( $pre2 ) ) );

		// -- do the request --------------------------------------------
		list( $result,, ) = $this->doApiRequestWithToken( $params );

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
		$actualFrom = $this->loadEntity( $result['from']['id'] );
		$this->assertEntityEquals( $expectedFrom, $actualFrom );
		$actualTo = $this->loadEntity( $result['to']['id'] );
		$this->assertEntityEquals( $expectedTo, $actualTo );

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
				'p' => array( 'fromid' => '{item}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //2 only to id
				'p' => array( 'toid' => '{item}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //3 toid bad
				'p' => array( 'fromid' => '{item}', 'toid' => 'ABCDE' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-invalid' ) ) ),
			array( //4 fromid bad
				'p' => array( 'fromid' => 'ABCDE', 'toid' => '{item}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-invalid' ) ) ),
			array( //5 both same id
				'p' => array( 'fromid' => '{Item}', 'toid' => '{item}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-invalid', 'message' => 'You must provide unique ids' ) ) ),
			array( //6 from id is property
				'p' => array( 'fromid' => '{prop}', 'toid' => '{item}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-item' ) ) ),
			array( //7 to id is property
				'p' => array( 'fromid' => '{item}', 'toid' => '{prop}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-item' ) ) ),
			array( //8 bad ignoreconficts (GETVALIDID is replaced by a valid id)
				'p' => array( 'fromid' => 'GETVALIDID', 'toid' => 'GETVALIDID', 'ignoreconflicts' => 'foo' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-invalid' ) ) ),
			array( //9 bad ignoreconficts (GETVALIDID is replaced by a valid id)
				'p' => array( 'fromid' => 'GETVALIDID', 'toid' => 'GETVALIDID', 'ignoreconflicts' => 'label|foo' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-invalid' ) ) ),
		);
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testMergeItemsParamsExceptions( $params, $expected ){
		$this->injectIds( $params );
		$this->injectIds( $expected );

		// -- set any defaults ------------------------------------
		$params['action'] = 'wbmergeitems';
		if( isset( $params['from'] ) && $params['from'] === 'GETVALIDID' ){
			$params['from'] = EntityTestHelper::getId( 'Empty' );
		}
		if( isset( $params['to'] ) && $params['to'] === 'GETVALIDID' ){
			$params['to'] = EntityTestHelper::getId( 'Empty2' );
		}
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
		$expected = array( 'exception' => array( 'type' => 'UsageException', 'code' => 'failed-save' ) );

		// -- prefill the entities --------------------------------------------
		$this->doApiRequestWithToken( array(
			'action' => 'wbeditentity',
			'id' => EntityTestHelper::getId( 'Empty' ) ,
			'clear' => '',
			'data' => json_encode( $pre1 ) ) );
		$this->doApiRequestWithToken( array(
			'action' => 'wbeditentity',
			'id' => EntityTestHelper::getId( 'Empty2' ) ,
			'clear' => '',
			'data' => json_encode( $pre2 ) ) );

		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => EntityTestHelper::getId( 'Empty' ),
			'toid' => EntityTestHelper::getId( 'Empty2' ),
		);

		// -- do the request --------------------------------------------
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

	public function testMergeNonExistingItem() {
		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q60457977',
			'toid' => 'Q60457978'
		);

		$expectedException = array( 'type' => 'UsageException', 'code' => 'no-such-entity' );
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	/**
	 * Applies self::$idMap to all data in the given data structure, recursively.
	 *
	 * @param $data
	 *
	 * @throws LogicException
	 */
	protected function injectIds( &$data ) {
		if ( !self::$hasSetup ) {
			throw new LogicException( 'setUp() was not yet completed.' );
		}

		$idMap = array(
			'{prop}' => lcfirst( self::$thePropertyId->getSerialization() ),
			'{item}' => lcfirst( self::$theItemId->getSerialization() ),
			'{Prop}' => ucfirst( self::$thePropertyId->getSerialization() ),
			'{Item}' => ucfirst( self::$theItemId->getSerialization() ),
		);

		EntityTestHelper::injectIds( $data, $idMap );
	}
}

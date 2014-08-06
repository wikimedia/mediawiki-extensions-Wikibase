<?php

namespace Wikibase\Test;

use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Specials\SpecialMergeItems;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialMergeItems
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 */
class SpecialMergeItemsTest extends SpecialPageTestBase {

	/**
	 * @var MockRepository
	 */
	private $repo = null;

	/**
	 * @var EntityModificationTestHelper
	 */
	private $entityModificationTestHelper = null;

	public function setUp() {
		parent::setUp();

		$this->entityModificationTestHelper = new EntityModificationTestHelper();

		$this->repo = $this->entityModificationTestHelper->getRepository();

		$this->entityModificationTestHelper->putEntities( array(
			'Q1' => array(),
			'Q2' => array(),
			'P1' => array( 'datatype' => 'string' ),
			'P2' => array( 'datatype' => 'string' ),
		) );

		$this->entityModificationTestHelper->putRedirects( array(
			'Q11' => 'Q1',
			'Q12' => 'Q2',
		) );
	}

	protected function newSpecialPage() {
		$specialMergeItems = new SpecialMergeItems();
		$this->overrideServices( $specialMergeItems );

		return $specialMergeItems;
	}

	/**
	 * @param SpecialMergeItems $page
	 */
	private function overrideServices( SpecialMergeItems $page ) {
		$user = $GLOBALS['wgUser'];

		$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();

		$changeOpsFactory = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()->getMergeChangeOpFactory();
		$exceptionLocalizer = WikibaseRepo::getDefaultInstance()->getExceptionLocalizer();

		$page->initServices(
			$idParser,
			$exceptionLocalizer,
			new ItemMergeInteractor(
				$changeOpsFactory,
				$this->repo,
				$this->repo,
				$this->getPermissionCheckers(),
				$summaryFormatter,
				$user
			)
		);
	}

	private function executeSpecialMergeItems( $params ) {
		//FIXME: force user for permission test
		list( $html, ) =  $this->executeSpecialPage( '', new \FauxRequest( $params ) );
		return $html;
	}

	public function testForm() {
		$matchers['fromid'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-mergeitems-fromid',
				'class' => 'wb-input',
				'name' => 'fromid',
			) );
		$matchers['toid'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-mergeitems-toid',
				'class' => 'wb-input',
				'name' => 'toid',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-mergeitems-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-mergeitems-submit',
			) );

		$output = $this->executeSpecialMergeItems( array() );

		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

	private function getPermissionCheckers() {
		$permissionChecker = $this->getMock( 'Wikibase\EntityPermissionChecker' );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user, $permission, EntityId $id ) {
				if ( $user->getName() === 'UserWithoutPermission' && $permission === 'edit' ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}

	public static function provideData(){
		$testCases = array();
		$testCases['labelMerge'] = array(
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			array(),
			array(),
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
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
			array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal', 'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ) ) ) ),
			array(),
			array(),
			array( 'claims' => array( 'P1' => array ( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal' ) ) ) ),
		);

		return $testCases;
	}

	/**
	 * @dataProvider provideData
	 */
	function testMergeRequest( $pre1, $pre2, $expectedFrom, $expectedTo, $ignoreConflicts = null ){
		// -- set up params ---------------------------------
		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q1',
			'toid' => 'Q2',
			'summary' => 'CustomSummary!',
		);
		if( $ignoreConflicts !== null ){
			$params['ignoreconflicts'] = $ignoreConflicts;
		}

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $pre1, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $pre2, 'Q2' );

		// -- do the request --------------------------------------------
		$html = $this->executeSpecialMergeItems( $params );

		// -- check the result --------------------------------------------
		$this->assertNoError( $html );
		$this->assertSuccess( 'Q1', 'Q2', $html );

		// -- check the items --------------------------------------------
		$actualFrom = $this->entityModificationTestHelper->getEntity( 'Q1' );
		$this->entityModificationTestHelper->assertEntityEquals( $expectedFrom, $actualFrom );

		$actualTo = $this->entityModificationTestHelper->getEntity( 'Q2' );
		$this->entityModificationTestHelper->assertEntityEquals( $expectedTo, $actualTo );
	}

	public static function provideExceptionParamsData() {
		return array(
			array( //0 no ids given
				'p' => array( ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //1 only from id
				'p' => array( 'fromid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //2 only to id
				'p' => array( 'toid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //3 toid bad
				'p' => array( 'fromid' => 'Q1', 'toid' => 'ABCDE' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
			array( //4 fromid bad
				'p' => array( 'fromid' => 'ABCDE', 'toid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
			array( //5 both same id
				'p' => array( 'fromid' => 'Q1', 'toid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id', 'message' => 'You must provide unique ids' ) ) ),
			array( //6 from id is property
				'p' => array( 'fromid' => 'P1', 'toid' => 'Q1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-item' ) ) ),
			array( //7 to id is property
				'p' => array( 'fromid' => 'Q1', 'toid' => 'P1' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-item' ) ) ),
			array( //8 bad ignoreconficts
				'p' => array( 'fromid' => 'Q2', 'toid' => 'Q2', 'ignoreconflicts' => 'foo' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
			array( //9 bad ignoreconficts
				'p' => array( 'fromid' => 'Q2', 'toid' => 'Q2', 'ignoreconflicts' => 'label|foo' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id' ) ) ),
		);
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testMergeItemsParamsExceptions( $params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbmergeitems';

		$html = $this->executeSpecialMergeItems( $params );
		$this->assertError( $expected, $html );
	}

	public static function provideExceptionConflictsData() {
		return array(
			array(
				array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
				array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo2' ) ) ),
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
		$this->entityModificationTestHelper->putEntity( $pre1, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $pre2, 'Q2' );

		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q1',
			'toid' => 'Q2',
		);

		// -- do the request --------------------------------------------
		$html = $this->executeSpecialMergeItems( $params );
		$this->assertError( $expected, $html );
	}

	public function testMergeNonExistingItem() {
		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q60457977',
			'toid' => 'Q60457978'
		);

		try {
			$html = $this->executeSpecialMergeItems( $params );
			$this->fail( 'Expected UsageException!' );
		} catch ( \UsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( 'no-such-entity', $ex );
		}
	}


}
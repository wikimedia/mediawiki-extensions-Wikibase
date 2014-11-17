<?php

namespace Wikibase\Test;

use Exception;
use MessageException;
use PHPUnit_Framework_Error;
use RawMessage;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
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
	 * @var MockRepository|null
	 */
	private $mockRepository = null;

	/**
	 * @var User|null
	 */
	private $user = null;

	/**
	 * @var EntityModificationTestHelper
	 */
	private $entityModificationTestHelper = null;

	protected function setUp() {
		parent::setUp();

		$this->entityModificationTestHelper = new EntityModificationTestHelper();

		$this->mockRepository = $this->entityModificationTestHelper->getMockRepository();

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
		$this->overrideServices( $specialMergeItems, $this->user );

		return $specialMergeItems;
	}

	/**
	 * @param SpecialMergeItems $page
	 */
	private function overrideServices( SpecialMergeItems $page, User $user ) {
		$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();

		$changeOpsFactory = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()->getMergeChangeOpFactory();

		$exceptionLocalizer = $this->getMock( 'Wikibase\Lib\Localizer\ExceptionLocalizer' );
		$exceptionLocalizer->expects( $this->any() )
			->method( 'getExceptionMessage' )
			->will( $this->returnCallback( array( $this, 'getExceptionMessage' ) ) );

		$page->initServices(
			$idParser,
			$exceptionLocalizer,
			new TokenCheckInteractor(
				$user
			),
			new ItemMergeInteractor(
				$changeOpsFactory,
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionCheckers(),
				$summaryFormatter,
				$user
			)
		);
	}

	public function getExceptionMessage( Exception $ex ) {
		if ( $ex instanceof PHPUnit_Framework_Error ) {
			throw $ex;
		}

		$text = get_class( $ex );

		if ( $ex instanceof MessageException ) {
			$text .= ':' . $ex->getKey();
		} elseif ( $ex instanceof ItemMergeException ) {
			$text .= ':' . $ex->getErrorCode();
		} elseif ( $ex instanceof TokenCheckException ) {
			$text .= ':' . $ex->getErrorCode();
		} else {
			$text .= ':"' . $ex->getMessage() . '"';
		}

		return new RawMessage( '(@' . $text . '@)' );
	}

	private function executeSpecialMergeItems( $params, User $user = null ) {
		if ( !$user ) {
			$user = $GLOBALS['wgUser'];
		}

		// HACK: we need this in newSpecialPage, but executeSpecialPage doesn't pass the context on.
		$this->user = $user;

		if ( !isset( $params['token'] ) ) {
			$params['token'] = $user->getEditToken();
		}

		$request = new \FauxRequest( $params, true );
		list( $html, ) =  $this->executeSpecialPage( '', $request, 'qqx' );
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

		$this->assertNoError( $output );

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

	private function getPermissionCheckers() {
		$permissionChecker = $this->getMock( 'Wikibase\Repo\Store\EntityPermissionChecker' );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user, $permission, EntityId $id ) {
				$name = 'UserWithoutPermission-' . $permission;
				if ( $user->getName() === $name ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}

	private function assertError( $error, $html ) {
		// match error strings as generated by $this->getExceptionMessage!
		if ( !preg_match( '!<p class="error">\(@(.*?)@\)</p>!', $html, $m ) ) {
			$this->fail( 'Expected error ' . $error . '. No error found in page!' );
		}

		$this->assertEquals( $error, $m[1], 'Expected error ' . $error );
	}

	private function assertNoError( $html ) {
		$pattern = '!<p class="error"!';
		$this->assertNotRegExp( $pattern, $html, 'Expected no error!' );
	}

	public function mergeRequestProvider() {
		$testCases = array();
		$testCases['labelMerge'] = array(
			array( 'labels' => array(
				'en' => array( 'language' => 'en', 'value' => 'foo' )
			) ),
			array(),
			array(),
			array( 'labels' => array(
				'en' => array( 'language' => 'en', 'value' => 'foo' )
			) ),
		);
		$testCases['IgnoreConflictSitelinksMerge'] = array(
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainFrom' ),
				'enwiki' => array( 'site' => 'enwiki', 'title' => 'PlFrom' ),
			) ),
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainTo' )
			) ),
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainFrom' )
			) ),
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainTo' ),
				'enwiki' => array( 'site' => 'enwiki', 'title' => 'PlFrom' ),
			) ),
			'sitelink|foo'
		);

		$statement = array(
			'mainsnak' => array(
				'snaktype' => 'value',
				'property' => 'P1',
				'datavalue' => array( 'value' => 'imastring', 'type' => 'string' )
			),
			'type' => 'statement',
			'rank' => 'normal',
			'id' => 'deadbeefdeadbeefdeadbeefdeadbeef'
		);

		$statementWithoutId = $statement;
		unset( $statementWithoutId['id'] );

		$testCases['claimMerge'] = array(
			array( 'claims' => array( 'P1' => array( $statement ) ) ),
			array(),
			array(),
			array( 'claims' => array( 'P1' => array ( $statementWithoutId ) ) ),
		);

		return $testCases;
	}

	/**
	 * @dataProvider mergeRequestProvider
	 */
	public function testMergeRequest( $fromBefore, $toBefore, $fromAfter, $toAfter, $ignoreConflicts = '' ) {

		// -- set up params ---------------------------------
		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q1',
			'toid' => 'Q2',
			'summary' => 'CustomSummary!',
			'ignoreconflicts' => $ignoreConflicts,
		);

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $fromBefore, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $toBefore, 'Q2' );

		// -- do the request --------------------------------------------
		$html = $this->executeSpecialMergeItems( $params );

		// -- check the result --------------------------------------------
		$this->assertNoError( $html );
		$this->assertRegExp( '!\(wikibase-mergeitems-success: Q1, [0-9]+, Q2, [0-9]+\)!', $html, 'Expected success message' );

		// -- check the items --------------------------------------------
		$actualFrom = $this->entityModificationTestHelper->getEntity( 'Q1' );
		$this->entityModificationTestHelper->assertEntityEquals( $fromAfter, $actualFrom );

		$actualTo = $this->entityModificationTestHelper->getEntity( 'Q2' );
		$this->entityModificationTestHelper->assertEntityEquals( $toAfter, $actualTo );
	}

	public function provideExceptionParamsData() {
		return array(
			array( //3 toid bad
				'p' => array( 'fromid' => 'Q1', 'toid' => 'ABCDE' ),
				'e' =>  'UserInputException:wikibase-wikibaserepopage-invalid-id' ),
			array( //4 fromid bad
				'p' => array( 'fromid' => 'ABCDE', 'toid' => 'Q1' ),
				'e' =>  'UserInputException:wikibase-wikibaserepopage-invalid-id' ),
			array( //5 both same id
				'p' => array( 'fromid' => 'Q1', 'toid' => 'Q1' ),
				'e' =>  'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-cant-merge-self' ),
			array( //6 from id is property
				'p' => array( 'fromid' => 'P1', 'toid' => 'Q1' ),
				'e' =>  'UserInputException:wikibase-itemmerge-not-item' ),
			array( //7 to id is property
				'p' => array( 'fromid' => 'Q1', 'toid' => 'P1' ),
				'e' =>  'UserInputException:wikibase-itemmerge-not-item' ),
			array( //10 bad token
				'p' => array( 'fromid' => 'Q1', 'toid' => 'Q2', 'token' => 'BAD' ),
				'e' =>  'Wikibase\Repo\Interactors\TokenCheckException:wikibase-tokencheck-badtoken' ),
		);
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testMergeItemsParamsExceptions( $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbmergeitems';

		$html = $this->executeSpecialMergeItems( $params );
		$this->assertError( $expected, $html );
	}

	public function provideExceptionConflictsData() {
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
	public function testMergeItemsConflictsExceptions( $pre1, $pre2 ) {
		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $pre1, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $pre2, 'Q2' );

		$params = array(
			'fromid' => 'Q1',
			'toid' => 'Q2',
		);

		// -- do the request --------------------------------------------
		$html = $this->executeSpecialMergeItems( $params );
		$this->assertError( 'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-failed-modify', $html );
	}

	public function testMergeNonExistingItem() {
		$params = array(
			'fromid' => 'Q60457977',
			'toid' => 'Q60457978'
		);

		$html = $this->executeSpecialMergeItems( $params );
		$this->assertError( 'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-no-such-entity', $html );
	}

	public function permissionProvider() {
		return array(
			'edit' => array( 'edit' ),
			'item-merge' => array( 'item-merge' ),
		);
	}

	/**
	 * @dataProvider permissionProvider
	 */
	public function testNoPermission( $permission ) {
		$params = array(
			'fromid' => 'Q1',
			'toid' => 'Q2'
		);

		$user = User::newFromName( 'UserWithoutPermission-' . $permission );

		$html = $this->executeSpecialMergeItems( $params, $user );
		$this->assertError( 'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-permissiondenied', $html );
	}

}

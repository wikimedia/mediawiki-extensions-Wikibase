<?php

namespace Wikibase\Repo\Tests\Specials;

use Exception;
use HashSiteStore;
use PHPUnit_Framework_Error;
use RawMessage;
use SpecialPageTestBase;
use Status;
use TestSites;
use Title;
use User;
use Wikibase\ChangeOp\MergeChangeOpsFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\MessageException;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\Specials\SpecialMergeItems;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\EntityModificationTestHelper;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Repo\Specials\SpecialMergeItems
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
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
	 * @var EntityModificationTestHelper|null
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
	 * @return EditFilterHookRunner
	 */
	public function getMockEditFilterHookRunner() {
		$mock = $this->getMockBuilder( 'Wikibase\Repo\Hooks\EditFilterHookRunner' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->any() )
			->method( 'run' )
			 ->will( $this->returnValue( Status::newGood() ) );

		return $mock;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return Title::newFromText( $entityId->getSerialization() );
			} ) );

		return $entityTitleLookup;
	}

	/**
	 * @param SpecialMergeItems $page
	 * @param User $user
	 */
	private function overrideServices( SpecialMergeItems $page, User $user ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$summaryFormatter = $wikibaseRepo->getSummaryFormatter();

		$changeOpsFactory = new MergeChangeOpsFactory(
			$wikibaseRepo->getEntityConstraintProvider(),
			$wikibaseRepo->getChangeOpFactoryProvider(),
			new HashSiteStore( TestSites::getSites() )
		);

		$exceptionLocalizer = $this->getMock( 'Wikibase\Repo\Localizer\ExceptionLocalizer' );
		$exceptionLocalizer->expects( $this->any() )
			->method( 'getExceptionMessage' )
			->will( $this->returnCallback( function( Exception $ex ) {
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
			} ) );

		$page->initServices(
			$wikibaseRepo->getEntityIdParser(),
			$exceptionLocalizer,
			new TokenCheckInteractor( $user ),
			new ItemMergeInteractor(
				$changeOpsFactory,
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionCheckers(),
				$summaryFormatter,
				$user,
				new RedirectCreationInteractor(
						$this->mockRepository,
						$this->mockRepository,
						$this->getPermissionCheckers(),
						$summaryFormatter,
						$user,
						$this->getMockEditFilterHookRunner(),
						$this->mockRepository,
						$this->getMockEntityTitleLookup()
				),
				$this->getEntityTitleLookup()
			)
		);
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockEntityTitleLookup() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForID' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$title = $this->getMock( 'Title' );
				$title->expects( $this->any() )
					->method( 'isDeleted' )
					->will( $this->returnValue( false ) );
				return $title;
			} ) );

		return $titleLookup;
	}

	private function executeSpecialMergeItems( $params, User $user = null ) {
		if ( !$user ) {
			$user = $GLOBALS['wgUser'];
			$this->setMwGlobals( 'wgGroupPermissions', array( '*' => array( 'item-merge' => true, 'edit' => true ) ) );
		}

		// HACK: we need this in newSpecialPage, but executeSpecialPage doesn't pass the context on.
		$this->user = $user;

		if ( !isset( $params['wpEditToken'] ) ) {
			$params['wpEditToken'] = $user->getEditToken();
		}

		$request = new \FauxRequest( $params, true );
		list( $html, ) = $this->executeSpecialPage( '', $request, 'qqx' );
		return $html;
	}

	public function testForm() {
		$matchers['fromid'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-mergeitems-fromid',
			),
			'child' => array(
				'tag' => 'input',
				'attributes' => array(
					'name' => 'fromid',
				)
			) );
		$matchers['toid'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-mergeitems-toid',
			),
			'child' => array(
				'tag' => 'input',
				'attributes' => array(
					'name' => 'toid',
				)
			) );
		$matchers['submit'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-mergeitems-submit',
			),
			'child' => array(
				'tag' => 'button',
				'attributes' => array(
					'type' => 'submit',
					'name' => 'wikibase-mergeitems-submit',
				)
			) );

		$output = $this->executeSpecialMergeItems( array() );

		$this->assertNoError( $output );

		foreach ( $matchers as $key => $matcher ) {
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
		// Match error strings as generated by the ExceptionLocalizer mock!
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
			array( 'claims' => array( 'P1' => array( $statementWithoutId ) ) ),
		);

		return $testCases;
	}

	/**
	 * @dataProvider mergeRequestProvider
	 */
	public function testMergeRequest( $fromBefore, $toBefore, $fromAfter, $toAfter, $ignoreConflicts = '' ) {

		// -- set up params ---------------------------------
		$params = array(
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
		$this->assertRegExp( '!\(wikibase-mergeitems-success: Q1, \d+, Q2, \d+\)!', $html, 'Expected success message' );

		// -- check the items --------------------------------------------
		$actualFrom = $this->entityModificationTestHelper->getEntity( 'Q1', true );
		$this->entityModificationTestHelper->assertEntityEquals( $fromAfter, $actualFrom );

		$actualTo = $this->entityModificationTestHelper->getEntity( 'Q2', true );
		$this->entityModificationTestHelper->assertEntityEquals( $toAfter, $actualTo );
	}

	public function provideExceptionParamsData() {
		return array(
			array( //3 toid bad
				'p' => array( 'fromid' => 'Q1', 'toid' => 'ABCDE' ),
				'e' => 'Wikibase\Lib\UserInputException:wikibase-wikibaserepopage-invalid-id' ),
			array( //4 fromid bad
				'p' => array( 'fromid' => 'ABCDE', 'toid' => 'Q1' ),
				'e' => 'Wikibase\Lib\UserInputException:wikibase-wikibaserepopage-invalid-id' ),
			array( //5 both same id
				'p' => array( 'fromid' => 'Q1', 'toid' => 'Q1' ),
				'e' => 'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-cant-merge-self' ),
			array( //6 from id is property
				'p' => array( 'fromid' => 'P1', 'toid' => 'Q1' ),
				'e' => 'Wikibase\Lib\UserInputException:wikibase-itemmerge-not-item' ),
			array( //7 to id is property
				'p' => array( 'fromid' => 'Q1', 'toid' => 'P1' ),
				'e' => 'Wikibase\Lib\UserInputException:wikibase-itemmerge-not-item' ),
			array( //10 bad token
				'p' => array( 'fromid' => 'Q1', 'toid' => 'Q2', 'wpEditToken' => 'BAD' ),
				'e' => 'Wikibase\Repo\Interactors\TokenCheckException:wikibase-tokencheck-badtoken' ),
		);
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testMergeItemsParamsExceptions( $params, $expected ) {
		$html = $this->executeSpecialMergeItems( $params );
		$this->assertError( $expected, $html );
	}

	public function provideExceptionConflictsData() {
		return array(
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
		$this->setMwGlobals( 'wgGroupPermissions', array( '*' => array(
			'item-merge' => $permission !== 'item-merge',
			'edit' => $permission !== 'edit'
		) ) );

		$user = User::newFromName( 'UserWithoutPermission-' . $permission );

		if ( $permission === 'item-merge' ) {
			$this->setExpectedException( 'PermissionsError' );
		}
		$html = $this->executeSpecialMergeItems( $params, $user );
		if ( $permission === 'edit' ) {
			$this->assertError( 'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-permissiondenied', $html );
		}
	}

}

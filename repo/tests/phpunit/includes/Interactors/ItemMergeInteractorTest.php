<?php

namespace Wikibase\Test\Interactors;

use ContentHandler;
use HashSiteStore;
use Status;
use TestSites;
use Title;
use User;
use Wikibase\ChangeOp\MergeChangeOpsFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\EntityModificationTestHelper;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Repo\Interactors\ItemMergeInteractor
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseInteractor
 * @group Database
 * @group medium
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
 */
class ItemMergeInteractorTest extends \MediaWikiTestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepository = null;

	/**
	 * @var EntityModificationTestHelper|null
	 */
	private $testHelper = null;

	protected function setUp() {
		parent::setUp();

		$this->testHelper = new EntityModificationTestHelper();

		$this->mockRepository = $this->testHelper->getMockRepository();

		$this->testHelper->putEntities( array(
			'Q1' => array(),
			'Q2' => array(),
			'P1' => array( 'datatype' => 'string' ),
			'P2' => array( 'datatype' => 'string' ),
		) );

		$this->testHelper->putRedirects( array(
			'Q11' => 'Q1',
			'Q12' => 'Q2',
		) );
	}

	/**
	 * @return EditFilterHookRunner
	 */
	public function getMockEditFilterHookRunner() {
		$mock = $this->getMockBuilder( EditFilterHookRunner::class )
			->setMethods( array( 'run' ) )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'run' )
			->will( $this->returnValue( Status::newGood() ) );

		return $mock;
	}

	/**
	 * @return EntityPermissionChecker
	 */
	private function getPermissionCheckers() {
		$permissionChecker = $this->getMock( EntityPermissionChecker::class );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user, $permission, EntityId $id ) {
				$userWithoutPermissionName = 'UserWithoutPermission-' . $permission;

				if ( $user->getName() === $userWithoutPermissionName ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$mock = $this->getMock( EntityTitleLookup::class );

		$mock->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$contentHandler = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
				return $contentHandler->getTitleForId( $id );
			} ) );

		return $mock;
	}

	/**
	 * @param User|null $user
	 *
	 * @return ItemMergeInteractor
	 */
	private function newInteractor( User $user = null ) {
		if ( !$user ) {
			$user = $GLOBALS['wgUser'];
		}

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$summaryFormatter = $wikibaseRepo->getSummaryFormatter();

		//XXX: we may want or need to mock some of these services
		$changeOpsFactory = new MergeChangeOpsFactory(
			$wikibaseRepo->getEntityConstraintProvider(),
			$wikibaseRepo->getChangeOpFactoryProvider(),
			new HashSiteStore( TestSites::getSites() )
		);

		$interactor = new ItemMergeInteractor(
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
		);

		return $interactor;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockEntityTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForID' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$title = $this->getMock( Title::class );
				$title->expects( $this->any() )
					->method( 'isDeleted' )
					->will( $this->returnValue( false ) );
				return $title;
			} ) );

		return $titleLookup;
	}

	public function mergeProvider() {
		// NOTE: Any empty arrays and any fields called 'id' or 'hash' get stripped
		//       from the result before comparing it to the expected value.

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
			array(),
			array(
				'labels' => array(
				'en' => array( 'language' => 'en', 'value' => 'bar' ),
				'de' => array( 'language' => 'de', 'value' => 'berlin' )
			),
				'aliases' => array( 'en' => array( array( 'language' => 'en', 'value' => 'foo' ) ) )
			),
			array( 'label' )
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
			array( 'description' )
		);
		$testCases['aliasesMerge'] = array(
			array( 'aliases' => array( "nl" => array( array( "language" => "nl", "value" => "Dickes B" ) ) ) ),
			array(),
			array(),
			array( 'aliases' => array( "nl" => array( array( "language" => "nl", "value" => "Dickes B" ) ) ) ),
		);
		$testCases['aliasesMerge2'] = array(
			array( 'aliases' => array( "nl" => array( array( "language" => "nl", "value" => "Ali1" ) ) ) ),
			array( 'aliases' => array( "nl" => array( array( "language" => "nl", "value" => "Ali2" ) ) ) ),
			array(),
			array( 'aliases' => array( 'nl' => array(
				array( 'language' => 'nl', 'value' => 'Ali2' ),
				array( 'language' => 'nl', 'value' => 'Ali1' )
			) ) ),
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
			array( 'sitelink' )
		);
		$testCases['claimMerge'] = array(
			array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal',
				'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ) ) ) ),
			array(),
			array(),
			array( 'claims' => array(
				'P1' => array(
					array( 'mainsnak' => array(
						'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
						'type' => 'statement', 'rank' => 'normal' )
				)
			) ),
		);
		$testCases['claimMerge2'] = array(
			array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring1', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal',
				'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ) ) ) ),
			array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring2', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal',
				'id' => 'deadb33fdeadb33fdeadb33fdeadb33f' ) ) ) ),
			array(),
			array( 'claims' => array(
				'P1' => array(
					array(
						'mainsnak' => array( 'snaktype' => 'value', 'property' => 'P1',
							'datavalue' => array( 'value' => 'imastring2', 'type' => 'string' ) ),
						'type' => 'statement',
						'rank' => 'normal'
					),
					array(
						'mainsnak' => array( 'snaktype' => 'value', 'property' => 'P1',
							'datavalue' => array( 'value' => 'imastring1', 'type' => 'string' ) ),
						'type' => 'statement',
						'rank' => 'normal'
					)
				)
			) ),
		);

		return $testCases;
	}

	/**
	 * @dataProvider mergeProvider
	 */
	public function testMergeItems(
		array $fromData,
		array $toData,
		array $expectedFrom,
		array $expectedTo,
		array $ignoreConflicts = array()
	) {
		$interactor = $this->newInteractor();

		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );

		$this->testHelper->putEntities( array(
			'Q1' => $fromData,
			'Q2' => $toData,
		) );

		User::newFromName( 'UTSysop' )
			->addWatch( $this->getEntityTitleLookup()->getTitleForId( $fromId ) );

		$interactor->mergeItems( $fromId, $toId, $ignoreConflicts, 'CustomSummary' );

		$actualTo = $this->testHelper->getEntity( $toId );
		$this->testHelper->assertEntityEquals( $expectedTo, $actualTo, 'modified target item' );

		$this->assertRedirectWorks( $expectedFrom, $fromId, $toId );

		$toRevId = $this->mockRepository->getLatestRevisionId( $toId );
		$this->testHelper->assertRevisionSummary(
			'@^/\* *wbmergeitems-from:0\|\|Q1 *\*/ *CustomSummary$@',
			$toRevId,
			'summary for target item'
		);

		$this->assertItemMergedIntoIsWatched( $toId );
	}

	private function assertRedirectWorks( $expectedFrom, ItemId $fromId, ItemId $toId ) {
		if ( empty( $expectedFrom ) ) {
			try {
				$this->testHelper->getEntity( $fromId );
				$this->fail( 'getEntity( ' . $fromId->getSerialization() . ' ) did not throw an UnresolvedRedirectException' );
			} catch ( RevisionedUnresolvedRedirectException $ex ) {
				$this->assertEquals( $toId->getSerialization(), $ex->getRedirectTargetId()->getSerialization() );
			}

		} else {
			$actualFrom = $this->testHelper->getEntity( $fromId );
			$this->testHelper->assertEntityEquals( $expectedFrom, $actualFrom, 'modified source item' );
		}
	}

	private function assertItemMergedIntoIsWatched( ItemId $toId ) {
		$this->assertTrue(
			User::newFromName( 'UTSysop' )
				->isWatched( $this->getEntityTitleLookup()->getTitleForId( $toId ) ),
			'Item merged into is being watched'
		);
	}

	public function mergeFailureProvider() {
		return array(
			'missing from' => array( new ItemId( 'Q100' ), new ItemId( 'Q2' ), array(), 'no-such-entity' ),
			'missing to' => array( new ItemId( 'Q1' ), new ItemId( 'Q200' ), array(), 'no-such-entity' ),
			'merge into self' => array( new ItemId( 'Q1' ), new ItemId( 'Q1' ), array(), 'cant-merge-self' ),
			'from redirect' => array( new ItemId( 'Q11' ), new ItemId( 'Q2' ), array(), 'cant-load-entity-content' ),
			'to redirect' => array( new ItemId( 'Q1' ), new ItemId( 'Q12' ), array(), 'cant-load-entity-content' ),
		);
	}

	/**
	 * @dataProvider mergeFailureProvider
	 */
	public function testMergeItems_failure(
		ItemId $fromId,
		ItemId $toId,
		$ignoreConflicts,
		$expectedErrorCode
	) {
		try {
			$interactor = $this->newInteractor();
			$interactor->mergeItems( $fromId, $toId, $ignoreConflicts );

			$this->fail( 'ItemMergeException expected' );
		} catch ( ItemMergeException $ex ) {
			$this->assertEquals( $expectedErrorCode, $ex->getErrorCode() );
		}
	}

	public function mergeConflictsProvider() {
		return array(
			array(
				array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
				array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'foo2' ) ) ),
				array()
			),
			array(
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo2' ) ) ),
				array()
			),
		);
	}

	/**
	 * @dataProvider mergeConflictsProvider
	 */
	public function testMergeItems_conflict( $fromData, $toData, $ignoreConflicts ) {
		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );

		$this->testHelper->putEntity( $fromData, $fromId );
		$this->testHelper->putEntity( $toData, $toId );

		try {
			$interactor = $this->newInteractor();
			$interactor->mergeItems( $fromId, $toId, $ignoreConflicts );

			$this->fail( 'ItemMergeException expected' );
		} catch ( ItemMergeException $ex ) {
			$this->assertEquals( 'failed-modify', $ex->getErrorCode() );
		}
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
	public function testSetRedirect_noPermission( $permission ) {
		$this->setExpectedException( ItemMergeException::class );

		$user = User::newFromName( 'UserWithoutPermission-' . $permission );

		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );

		$interactor = $this->newInteractor( $user );
		$interactor->mergeItems( $fromId, $toId );
	}

}

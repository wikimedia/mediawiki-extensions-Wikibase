<?php

namespace Wikibase\Repo\Tests\Interactors;

use ContentHandler;
use HashSiteStore;
use MediaWikiTestCase;
use Status;
use TestSites;
use Title;
use User;
use Wikibase\Repo\ChangeOp\MergeChangeOpsFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Tests\EntityModificationTestHelper;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Repo\Interactors\ItemMergeInteractor
 *
 * @group Wikibase
 * @group Database
 * @group medium
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
 */
class ItemMergeInteractorTest extends MediaWikiTestCase {

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

		$this->testHelper->putEntities( [
			'Q1' => [],
			'Q2' => [],
			'P1' => [ 'datatype' => 'string' ],
			'P2' => [ 'datatype' => 'string' ],
		] );

		$this->testHelper->putRedirects( [
			'Q11' => 'Q1',
			'Q12' => 'Q2',
		] );
	}

	/**
	 * @return EditFilterHookRunner
	 */
	public function getMockEditFilterHookRunner() {
		$mock = $this->getMockBuilder( EditFilterHookRunner::class )
			->setMethods( [ 'run' ] )
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
	private function getPermissionChecker() {
		$permissionChecker = $this->getMock( EntityPermissionChecker::class );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user ) {
				$userWithoutPermissionName = 'UserWithoutPermission';

				if ( $user->getName() === $userWithoutPermissionName ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}

	/**
	 * @return EntityTitleStoreLookup
	 */
	private function getEntityTitleLookup() {
		$mock = $this->getMock( EntityTitleStoreLookup::class );

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
			$this->getPermissionChecker(),
			$summaryFormatter,
			$user,
			new RedirectCreationInteractor(
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionChecker(),
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
	 * @return EntityTitleStoreLookup
	 */
	private function getMockEntityTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleStoreLookup::class );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
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

		$testCases = [];
		$testCases['labelMerge'] = [
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
			[],
			[],
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
		];
		$testCases['identicalLabelMerge'] = [
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
			[],
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
		];
		$testCases['ignoreConflictLabelMerge'] = [
			[ 'labels' => [
				'en' => [ 'language' => 'en', 'value' => 'foo' ],
				'de' => [ 'language' => 'de', 'value' => 'berlin' ]
			] ],
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'bar' ] ] ],
			[],
			[
				'labels' => [
				'en' => [ 'language' => 'en', 'value' => 'bar' ],
				'de' => [ 'language' => 'de', 'value' => 'berlin' ]
			],
				'aliases' => [ 'en' => [ [ 'language' => 'en', 'value' => 'foo' ] ] ]
			],
			[ 'label' ]
		];
		$testCases['descriptionMerge'] = [
			[ 'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ],
			[],
			[],
			[ 'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ],
		];
		$testCases['identicalDescriptionMerge'] = [
			[ 'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ],
			[ 'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ],
			[],
			[ 'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'foo' ] ] ],
		];
		$testCases['ignoreConflictDescriptionMerge'] = [
			[ 'descriptions' => [
				'en' => [ 'language' => 'en', 'value' => 'foo' ],
				'de' => [ 'language' => 'de', 'value' => 'berlin' ]
			] ],
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'bar' ] ] ],
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
			[ 'descriptions' => [
				'en' => [ 'language' => 'en', 'value' => 'bar' ],
				'de' => [ 'language' => 'de', 'value' => 'berlin' ]
			] ],
			[ 'description' ]
		];
		$testCases['aliasesMerge'] = [
			[ 'aliases' => [ "nl" => [ [ "language" => "nl", "value" => "Dickes B" ] ] ] ],
			[],
			[],
			[ 'aliases' => [ "nl" => [ [ "language" => "nl", "value" => "Dickes B" ] ] ] ],
		];
		$testCases['aliasesMerge2'] = [
			[ 'aliases' => [ "nl" => [ [ "language" => "nl", "value" => "Ali1" ] ] ] ],
			[ 'aliases' => [ "nl" => [ [ "language" => "nl", "value" => "Ali2" ] ] ] ],
			[],
			[ 'aliases' => [ 'nl' => [
				[ 'language' => 'nl', 'value' => 'Ali2' ],
				[ 'language' => 'nl', 'value' => 'Ali1' ]
			] ] ],
		];
		$testCases['sitelinksMerge'] = [
			[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo' ] ] ],
			[],
			[],
			[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo' ] ] ],
		];
		$testCases['IgnoreConflictSitelinksMerge'] = [
			[ 'sitelinks' => [
				'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainFrom' ],
				'enwiki' => [ 'site' => 'enwiki', 'title' => 'PlFrom' ],
			] ],
			[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainTo' ] ] ],
			[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainFrom' ] ] ],
			[ 'sitelinks' => [
				'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainTo' ],
				'enwiki' => [ 'site' => 'enwiki', 'title' => 'PlFrom' ],
			] ],
			[ 'sitelink' ]
		];
		$testCases['claimMerge'] = [
			[ 'claims' => [ 'P1' => [ [ 'mainsnak' => [
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [ 'value' => 'imastring', 'type' => 'string' ] ],
				'type' => 'statement', 'rank' => 'normal',
				'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ] ] ] ],
			[],
			[],
			[ 'claims' => [
				'P1' => [
					[ 'mainsnak' => [
						'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [ 'value' => 'imastring', 'type' => 'string' ] ],
						'type' => 'statement', 'rank' => 'normal' ]
				]
			] ],
		];
		$testCases['claimMerge2'] = [
			[ 'claims' => [ 'P1' => [ [ 'mainsnak' => [
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [ 'value' => 'imastring1', 'type' => 'string' ] ],
				'type' => 'statement', 'rank' => 'normal',
				'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ] ] ] ],
			[ 'claims' => [ 'P1' => [ [ 'mainsnak' => [
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [ 'value' => 'imastring2', 'type' => 'string' ] ],
				'type' => 'statement', 'rank' => 'normal',
				'id' => 'deadb33fdeadb33fdeadb33fdeadb33f' ] ] ] ],
			[],
			[ 'claims' => [
				'P1' => [
					[
						'mainsnak' => [ 'snaktype' => 'value', 'property' => 'P1',
							'datavalue' => [ 'value' => 'imastring2', 'type' => 'string' ] ],
						'type' => 'statement',
						'rank' => 'normal'
					],
					[
						'mainsnak' => [ 'snaktype' => 'value', 'property' => 'P1',
							'datavalue' => [ 'value' => 'imastring1', 'type' => 'string' ] ],
						'type' => 'statement',
						'rank' => 'normal'
					]
				]
			] ],
		];

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
		array $ignoreConflicts = []
	) {
		$entityTitleLookup = $this->getEntityTitleLookup();
		$interactor = $this->newInteractor();

		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );

		$this->testHelper->putEntities( [
			'Q1' => $fromData,
			'Q2' => $toData,
		] );

		$user = $this->getTestSysop()->getUser();
		$user->addWatch( $entityTitleLookup->getTitleForId( $fromId ) );

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

		$this->assertTrue(
			$user->isWatched( $entityTitleLookup->getTitleForId( $toId ) ),
			'Item merged into is being watched'
		);
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

	public function mergeFailureProvider() {
		return [
			'missing from' => [ new ItemId( 'Q100' ), new ItemId( 'Q2' ), [], 'no-such-entity' ],
			'missing to' => [ new ItemId( 'Q1' ), new ItemId( 'Q200' ), [], 'no-such-entity' ],
			'merge into self' => [ new ItemId( 'Q1' ), new ItemId( 'Q1' ), [], 'cant-merge-self' ],
			'from redirect' => [ new ItemId( 'Q11' ), new ItemId( 'Q2' ), [], 'cant-load-entity-content' ],
			'to redirect' => [ new ItemId( 'Q1' ), new ItemId( 'Q12' ), [], 'cant-load-entity-content' ],
		];
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
		return [
			[
				[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
				[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo2' ] ] ],
				[]
			],
			[
				[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo' ] ] ],
				[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo2' ] ] ],
				[]
			],
		];
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

	public function testSetRedirect_noPermission() {
		$this->setExpectedException( ItemMergeException::class );

		$user = User::newFromName( 'UserWithoutPermission' );

		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );

		$interactor = $this->newInteractor( $user );
		$interactor->mergeItems( $fromId, $toId );
	}

}

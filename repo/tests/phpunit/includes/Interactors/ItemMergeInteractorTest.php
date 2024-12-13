<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Interactors;

use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\RateLimiter;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Status\Status;
use MediaWiki\Tests\Site\TestSites;
use MediaWiki\Title\Title;
use MediaWiki\User\TempUser\CreateStatus;
use MediaWiki\User\TempUser\TempUserCreator;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Merge\MergeFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Tests\EntityModificationTestHelper;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Stats\NullStatsdDataFactory;

/**
 * @covers \Wikibase\Repo\Interactors\ItemMergeInteractor
 *
 * @group Wikibase
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
 */
class ItemMergeInteractorTest extends MediaWikiIntegrationTestCase {

	/** @var string User name that gets blocked by the permission checker. */
	private const USER_NAME_WITHOUT_PERMISSION = 'UserWithoutPermission';

	/** @var string User group that gets blocked by the edit filter. */
	private const USER_GROUP_WITH_EDIT_FILTER = 'UserWithEditFilter';

	private ?MockRepository $mockRepository = null;
	private ?EntityModificationTestHelper $testHelper = null;

	protected function setUp(): void {
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

	public function getMockEditFilterHookRunner(): EditFilterHookRunner {
		$mock = $this->getMockBuilder( EditFilterHookRunner::class )
			->onlyMethods( [ 'run' ] )
			->disableOriginalConstructor()
			->getMock();
		$mock->method( 'run' )
			->willReturnCallback( function ( $new, IContextSource $context, string $summary ) {
				$groups = $this->getServiceContainer()->getUserGroupManager()->getUserGroups( $context->getUser() );
				if ( in_array( self::USER_GROUP_WITH_EDIT_FILTER, $groups, true ) ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} );

		return $mock;
	}

	private function getPermissionChecker(): EntityPermissionChecker {
		$permissionChecker = $this->createMock( EntityPermissionChecker::class );

		$callback = function ( User $user ) {
			if ( $user->getName() === self::USER_NAME_WITHOUT_PERMISSION ) {
				return Status::newFatal( 'permissiondenied' );
			} else {
				return Status::newGood();
			}
		};
		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturnCallback( $callback );
		$permissionChecker->method( 'getPermissionStatusForEntity' )
			->willReturnCallback( $callback );

		return $permissionChecker;
	}

	private function getEntityTitleLookup(): EntityTitleStoreLookup {
		$mock = $this->createMock( EntityTitleStoreLookup::class );

		$mock->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				$contentHandler = $this->getServiceContainer()
					->getContentHandlerFactory()
					->getContentHandler( ItemContent::CONTENT_MODEL_ID );
				return $contentHandler->getTitleForId( $id );
			} );

		return $mock;
	}

	private function getContext( ?User $user = null ): IContextSource {
		if ( !$user ) {
			$user = $this->getTestUser()->getUser();
		}

		$context = new RequestContext();
		$context->setUser( $user );

		return $context;
	}

	private function newInteractor(): ItemMergeInteractor {
		$services = $this->getServiceContainer();
		$summaryFormatter = WikibaseRepo::getSummaryFormatter( $services );

		//XXX: we may want or need to mock some of these services
		$mergeFactory = new MergeFactory(
			WikibaseRepo::getEntityConstraintProvider( $services ),
			WikibaseRepo::getChangeOpFactoryProvider( $services ),
			new HashSiteStore( TestSites::getSites() )
		);

		$editFilterHookRunner = $this->getMockEditFilterHookRunner();

		$entityTitleLookup = $this->getMockEntityTitleLookup();
		$permissionChecker = $this->getPermissionChecker();
		$tempUserCreator = $services->getTempUserCreator();
		$editEntityFactory = new MediaWikiEditEntityFactory(
			$entityTitleLookup,
			$this->mockRepository,
			$this->mockRepository,
			$permissionChecker,
			new EntityDiffer(),
			new EntityPatcher(),
			$editFilterHookRunner,
			new NullStatsdDataFactory(),
			$services->getUserOptionsLookup(),
			$tempUserCreator,
			1024 * 1024,
			[ 'item' ]
		);

		$interactor = new ItemMergeInteractor(
			$mergeFactory,
			$this->mockRepository,
			$editEntityFactory,
			$permissionChecker,
			$summaryFormatter,
			new ItemRedirectCreationInteractor(
				$this->mockRepository,
				$this->mockRepository,
				$permissionChecker,
				$summaryFormatter,
				$editFilterHookRunner,
				$this->mockRepository,
				$entityTitleLookup,
				$tempUserCreator
			),
			$this->getEntityTitleLookup(),
			$services->getPermissionManager()
		);

		return $interactor;
	}

	private function getMockEntityTitleLookup(): EntityTitleStoreLookup {
		$titleLookup = $this->createMock( EntityTitleStoreLookup::class );

		$titleLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				$title = $this->createMock( Title::class );
				$title->method( 'isDeleted' )
					->willReturn( false );
				return $title;
			} );

		return $titleLookup;
	}

	public static function mergeProvider(): iterable {
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
				'de' => [ 'language' => 'de', 'value' => 'berlin' ],
			] ],
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'bar' ] ] ],
			[],
			[
				'labels' => [
				'en' => [ 'language' => 'en', 'value' => 'bar' ],
				'de' => [ 'language' => 'de', 'value' => 'berlin' ],
				],
				'aliases' => [ 'en' => [ [ 'language' => 'en', 'value' => 'foo' ] ] ],
			],
			[ 'label' ],
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
				'de' => [ 'language' => 'de', 'value' => 'berlin' ],
			] ],
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'bar' ] ] ],
			[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
			[ 'descriptions' => [
				'en' => [ 'language' => 'en', 'value' => 'bar' ],
				'de' => [ 'language' => 'de', 'value' => 'berlin' ],
			] ],
			[ 'description' ],
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
				[ 'language' => 'nl', 'value' => 'Ali1' ],
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
			[ 'sitelink' ],
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
						'type' => 'statement', 'rank' => 'normal' ],
				],
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
						'rank' => 'normal',
					],
					[
						'mainsnak' => [ 'snaktype' => 'value', 'property' => 'P1',
							'datavalue' => [ 'value' => 'imastring1', 'type' => 'string' ] ],
						'type' => 'statement',
						'rank' => 'normal',
					],
				],
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
		$watchlistManager = MediaWikiServices::getInstance()->getWatchlistManager();
		$watchlistManager->addWatch( $user, $entityTitleLookup->getTitleForId( $fromId ) );

		$tag = __METHOD__ . '-tag';

		$status = $interactor->mergeItems( $fromId, $toId, $this->getContext(), $ignoreConflicts, 'CustomSummary', false, [ $tag ] );

		$actualTo = $this->testHelper->getEntity( $toId );
		$this->testHelper->assertEntityEquals( $expectedTo, $actualTo, 'modified target item' );

		$this->assertRedirectWorks( $expectedFrom, $fromId, $toId );

		$fromRevId = $status->getFromEntityRevision()->getRevisionId();
		$toRevId = $status->getToEntityRevision()->getRevisionId();
		$this->testHelper->assertRevisionSummary(
			'@^/\* *wbmergeitems-from:0\|\|Q1 *\*/ *CustomSummary$@',
			$toRevId,
			'summary for target item'
		);

		$this->assertTrue(
			$watchlistManager->isWatched( $user, $entityTitleLookup->getTitleForId( $toId ) ),
			'Item merged into is being watched'
		);

		$this->assertContains( $tag, $this->mockRepository->getLogEntry( $fromRevId )['tags'],
			'Edit on item merged from is tagged' );
		$this->assertContains( $tag, $this->mockRepository->getLogEntry( $toRevId )['tags'],
			'Edit on item merged into is tagged' );

		$this->assertNull( $status->getSavedTempUser() );
	}

	private function assertRedirectWorks( $expectedFrom, ItemId $fromId, ItemId $toId ): void {
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

	public static function mergeFailureProvider(): iterable {
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
		array $ignoreConflicts,
		string $expectedErrorCode
	): void {
		try {
			$interactor = $this->newInteractor();
			$interactor->mergeItems( $fromId, $toId, $this->getContext(), $ignoreConflicts );

			$this->fail( 'ItemMergeException expected' );
		} catch ( ItemMergeException $ex ) {
			$this->assertEquals( $expectedErrorCode, $ex->getErrorCode() );
		}
	}

	public static function mergeConflictsProvider(): iterable {
		return [
			[
				[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
				[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo2' ] ] ],
				[],
			],
			[
				[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo' ] ] ],
				[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo2' ] ] ],
				[],
			],
		];
	}

	/**
	 * @dataProvider mergeConflictsProvider
	 */
	public function testMergeItems_conflict( array $fromData, array $toData, array $ignoreConflicts ): void {
		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );

		$this->testHelper->putEntity( $fromData, $fromId );
		$this->testHelper->putEntity( $toData, $toId );

		try {
			$interactor = $this->newInteractor();
			$interactor->mergeItems( $fromId, $toId, $this->getContext(), $ignoreConflicts );

			$this->fail( 'ItemMergeException expected' );
		} catch ( ItemMergeException $ex ) {
			$this->assertEquals( 'failed-modify', $ex->getErrorCode() );
		}
	}

	public function testMergeItems_noPermission() {
		$this->expectException( ItemMergeException::class );

		$user = User::newFromName( self::USER_NAME_WITHOUT_PERMISSION );

		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );

		$interactor = $this->newInteractor();
		$interactor->mergeItems( $fromId, $toId, $this->getContext( $user ) );
	}

	public function testMergeItems_editFilter(): void {
		$user = $this->getTestUser( self::USER_GROUP_WITH_EDIT_FILTER )->getUser();
		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );
		$interactor = $this->newInteractor();

		$this->expectException( ItemMergeException::class );
		$interactor->mergeItems( $fromId, $toId, $this->getContext( $user ) );
	}

	public function testMergeItems_rateLimit(): void {
		$rateLimiter = $this->createConfiguredMock( RateLimiter::class, [
			'isLimitable' => true,
			'limit' => true, // limit was exceeded
		] );
		$this->setService( 'RateLimiter', $rateLimiter );

		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );
		$interactor = $this->newInteractor();

		$this->expectException( ItemMergeException::class );
		$interactor->mergeItems( $fromId, $toId, $this->getContext() );
	}

	public function testMergeItems_tempUser(): void {
		$anonUser = $this->getServiceContainer()->getUserFactory()->newAnonymous();
		$originalContext = new RequestContext();
		$originalContext->setUser( $anonUser );
		$tempUser = $this->getTestUser()->getUser();
		$tempUserCreator = $this->createMock( TempUserCreator::class );
		$tempUserCreator->method( 'shouldAutoCreate' )
			->willReturnCallback( fn( Authority $authority, $action ) => !$authority->isRegistered() );
		$tempUserCreator->expects( $this->once() )
			->method( 'create' )
			->willReturn( CreateStatus::newGood( $tempUser ) );
		$this->setService( 'TempUserCreator', $tempUserCreator );

		$fromId = new ItemId( 'Q1' );
		$toId = new ItemId( 'Q2' );
		$this->testHelper->putEntities( [
			'Q1' => [ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'en label' ] ] ],
			'Q2' => [ 'labels' => [ 'de' => [ 'language' => 'de', 'value' => 'de label' ] ] ],
		] );

		$status = $this->newInteractor()->mergeItems( $fromId, $toId, $originalContext );

		// assert that proper new context is returned and old context is not modified
		$this->assertSame( $tempUser, $status->getSavedTempUser() );
		$newContext = $status->getContext();
		$this->assertNotSame( $originalContext, $newContext );
		$this->assertSame( $tempUser, $newContext->getUser() );
		$this->assertSame( $anonUser, $originalContext->getUser() );

		// assert that all three edits (two on Q1, one on Q2) were made by the same user
		$edit3 = $this->mockRepository->getLatestLogEntryFor( $fromId );
		$edit2 = $this->mockRepository->getLatestLogEntryFor( $toId );
		$this->assertSame( $edit2['revision'] + 1, $edit3['revision'] );
		$edit1 = $this->mockRepository->getLogEntry( $edit2['revision'] - 1 );
		$this->assertSame( $fromId->getSerialization(), $edit1['entity'] );
		$this->assertSame( $tempUser->getName(), $edit1['user'] );
		$this->assertSame( $tempUser->getName(), $edit2['user'] );
		$this->assertSame( $tempUser->getName(), $edit3['user'] );
	}

}

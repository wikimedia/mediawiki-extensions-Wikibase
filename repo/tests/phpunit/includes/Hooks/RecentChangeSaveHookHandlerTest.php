<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Hooks;

use CentralIdLookup;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RecentChange;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Repo\Hooks\RecentChangeSaveHookHandler;
use Wikibase\Repo\Notifications\ChangeHolder;
use Wikibase\Repo\Store\SubscriptionLookup;
use WikiMap;

/**
 * @covers \Wikibase\Repo\Hooks\RecentChangeSaveHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RecentChangeSaveHookHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var ChangeHolder
	 */
	private $changeHolder;

	/**
	 * @var ChangeStore|MockObject
	 */
	private $changeStore;

	/**
	 * @var null|CentralIdLookup|MockObject
	 */
	private $centralIdLookup;
	/**
	 * @var SubscriptionLookup|MockObject
	 */
	private $subscriptionLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->changeHolder = new ChangeHolder();
		$this->changeStore = $this->createStub( ChangeStore::class );
		$this->changeStore->method( 'saveChange' )->willReturnCallback( function ( $change ) {
			$change->setField( 'id', 123 );
		} );
		$this->centralIdLookup = null; // CentralIdLookupFactory::getNonLocalLookup() may return null in the hook's factory function
		$this->subscriptionLookup = $this->createMock( SubscriptionLookup::class );
	}

	public function testGivenRecentChangeForEntityChange_addsMetaDataToEntityChange() {
		$recentChangeAttrs = [
			'rc_timestamp' => 1234567890,
			'rc_bot' => 1,
			'rc_cur_id' => 42,
			'rc_last_oldid' => 776,
			'rc_this_oldid' => 777,
			'rc_comment' => 'edit summary',
			'rc_user' => 321,
			'rc_user_text' => 'some_user',
		];
		$recentChange = $this->newStubRecentChangeWithAttributes( $recentChangeAttrs );
		$entityChange = $this->newEntityChange();

		$this->changeHolder->transmitChange( $entityChange );
		$this->subscriptionLookup->method( 'getSubscribers' )
			->willReturn( [ 'enwiki' ] );

		$this->newHookHandler()->onRecentChange_save( $recentChange );

		$changeMetaData = $entityChange->getMetadata();
		$this->assertSame( $recentChangeAttrs['rc_bot'], $changeMetaData['bot'] );
		$this->assertSame( $recentChangeAttrs['rc_cur_id'], $changeMetaData['page_id'] );
		$this->assertSame( $recentChangeAttrs['rc_this_oldid'], $changeMetaData['rev_id'] );
		$this->assertSame( $recentChangeAttrs['rc_last_oldid'], $changeMetaData['parent_id'] );
		$this->assertSame( $recentChangeAttrs['rc_comment'], $changeMetaData['comment'] );
	}

	public function testGivenCentralIdLookupAndRecentChangeWithUser_addsUserIdToEntityChange() {
		$expectedUserId = 123;
		$testUser = $this->getTestUser()->getUser();
		$recentChangeAttrs = [
			'rc_this_oldid' => 777,
			'rc_user' => $testUser->getId(),
			'rc_user_text' => $testUser->getName(),
		];
		$recentChange = $this->newStubRecentChangeWithAttributes( $recentChangeAttrs );
		$recentChange->method( 'getPerformerIdentity' )
			->willReturn( $testUser );
		$entityChange = $this->newEntityChange();

		$this->changeHolder->transmitChange( $entityChange );
		$this->subscriptionLookup->method( 'getSubscribers' )
			->willReturn( [ 'enwiki' ] );

		$this->centralIdLookup = $this->createMock( CentralIdLookup::class );
		$this->centralIdLookup->expects( $this->once() )
			->method( 'centralIdFromLocalUser' )
			->willReturn( $expectedUserId );

		$this->newHookHandler()->onRecentChange_save( $recentChange );

		$this->assertSame( $testUser->getId(), $entityChange->getField( 'user_id' ) );

		$changeMetaData = $entityChange->getMetadata();
		$this->assertSame( $testUser->getName(), $changeMetaData['user_text'] );
		$this->assertSame( $expectedUserId, $changeMetaData['central_user_id'] );
	}

	public function testGivenRecentChangeForEntityChange_skipsAddingMetaDataToEntityChange() {
		$recentChangeAttrs = [
			'rc_timestamp' => 1234567890,
			'rc_bot' => 1,
			'rc_cur_id' => 42,
			'rc_last_oldid' => 776,
			'rc_this_oldid' => 777,
			'rc_comment' => 'edit summary',
			'rc_user' => 321,
			'rc_user_text' => 'some_user',
		];
		$recentChange = $this->newStubRecentChangeWithAttributes( $recentChangeAttrs );
		$entityChange = $this->newEntityChange();

		$this->changeHolder->transmitChange( $entityChange );
		$this->subscriptionLookup->method( 'getSubscribers' )
			->willReturn( [] );

		$this->newHookHandler()->onRecentChange_save( $recentChange );

		$changeMetaData = $entityChange->getMetadata();
		$this->assertArrayNotHasKey( 'bot', $changeMetaData );
		$this->assertArrayNotHasKey( 'page_id', $changeMetaData );
		$this->assertArrayNotHasKey( 'rev_id', $changeMetaData );
		$this->assertArrayNotHasKey( 'parent_id', $changeMetaData );
		$this->assertArrayNotHasKey( 'comment', $changeMetaData );
	}

	public function testGivenRecentChangeForEntityChange_schedulesDispatchJobForEntityChange() {
		$recentChangeAttrs = [
			'rc_timestamp' => 1234567890,
			'rc_bot' => 1,
			'rc_cur_id' => 42,
			'rc_last_oldid' => 776,
			'rc_this_oldid' => 777,
			'rc_comment' => 'edit summary',
			'rc_user' => 321,
			'rc_user_text' => 'some_user',
		];
		$recentChange = $this->newStubRecentChangeWithAttributes( $recentChangeAttrs );
		$entityChange = $this->newEntityChange();

		$this->changeHolder->transmitChange( $entityChange );
		$this->subscriptionLookup->method( 'getSubscribers' )
			->willReturn( [ 'enwiki' ] );

		$this->newHookHandler()->onRecentChange_save( $recentChange );

		$wiki = WikiMap::getCurrentWikiDbDomain()->getId();
		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup( $wiki );
		$queuedJobs = $jobQueueGroup->get( 'DispatchChanges' )->getAllQueuedJobs();
		$job = $queuedJobs->current();
		$this->assertNotNull( $job );
		$actualEntityId = $job->getParams()['entityId'];
		$this->assertSame( $entityChange->getEntityId()->getSerialization(), $actualEntityId );
	}

	public function testGivenRecentChangeForEntityChangeWithoutSubscribers_skipsSchedulingDispatchJob() {
		$recentChangeAttrs = [
			'rc_timestamp' => 1234567890,
			'rc_bot' => 1,
			'rc_cur_id' => 42,
			'rc_last_oldid' => 776,
			'rc_this_oldid' => 777,
			'rc_comment' => 'edit summary',
			'rc_user' => 321,
			'rc_user_text' => 'some_user',
		];
		$recentChange = $this->newStubRecentChangeWithAttributes( $recentChangeAttrs );
		$entityChange = $this->newEntityChange();

		$this->changeHolder->transmitChange( $entityChange );
		$this->subscriptionLookup->method( 'getSubscribers' )
			->willReturn( [] );

		$this->newHookHandler()->onRecentChange_save( $recentChange );

		$wiki = WikiMap::getCurrentWikiDbDomain()->getId();
		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup( $wiki );
		$this->assertTrue( $jobQueueGroup->get( 'DispatchChanges' )->isEmpty() );
	}

	public function testGivenRecentChangeForAddingSitelink_schedulesDispatchJob() {
		$recentChangeAttrs = [
			'rc_timestamp' => 1234567890,
			'rc_bot' => 1,
			'rc_cur_id' => 42,
			'rc_last_oldid' => 776,
			'rc_this_oldid' => 777,
			'rc_comment' => 'edit summary',
			'rc_user' => 321,
			'rc_user_text' => 'some_user',
		];
		$recentChange = $this->newStubRecentChangeWithAttributes( $recentChangeAttrs );
		$testItemChange = new ItemChange( [
			'time' => '20210906122813',
			'info' => [
				'compactDiff' => new EntityDiffChangedAspects( [], [], [], [
					'some_wiki' => [ null, 'some_page', false ],
				], false ),
				'metadata' => [
					'page_id' => 3,
					'rev_id' => 123,
					'parent_id' => 4,
					'comment' => '...',
					'user_text' => 'Admin',
					'central_user_id' => 0,
					'bot' => 0,
				],
			],
			'user_id' => '43',
			'revision_id' => '123',
			'object_id' => 'Q50',
			'type' => 'wikibase-item~update',
		] );
		$this->changeHolder->transmitChange( $testItemChange );
		$this->subscriptionLookup->method( 'getSubscribers' )
			->willReturn( [] );

		$this->newHookHandler()->onRecentChange_save( $recentChange );

		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup(
			WikiMap::getCurrentWikiDbDomain()->getId()
		);
		$this->assertFalse( $jobQueueGroup->get( 'DispatchChanges' )->isEmpty() );
	}

	public function logTypeProvider(): array {
		return [
			'Entity deletion' => [ true, 'delete', 'delete' ],
			'Entity undeletion' => [ true, 'delete', 'restore' ],
			'Revision deletion' => [ false, 'delete', 'revision' ],
			'Misc. log action' => [ false, 'blah', 'blah' ],
		];
	}

	/**
	 * @dataProvider logTypeProvider
	 */
	public function testGivenRecentChangeForLogType( bool $changeUpdated, string $logType, string $logAction ) {
		$recentChangeAttrs = [
			'rc_timestamp' => 1234567890,
			'rc_bot' => 1,
			'rc_cur_id' => 42,
			'rc_last_oldid' => 0,
			'rc_this_oldid' => 0,
			'rc_comment' => 'summary',
			'rc_user' => 321,
			'rc_user_text' => 'some_user',
			'rc_log_type' => $logType,
			'rc_log_action' => $logAction,
		];
		$recentChange = $this->newStubRecentChangeWithAttributes( $recentChangeAttrs );
		$entityChange = $this->newEntityChange();

		$this->changeHolder->transmitChange( $entityChange );
		$this->subscriptionLookup->method( 'getSubscribers' )
			->willReturn( [ 'enwiki' ] );

		$this->newHookHandler()->onRecentChange_save( $recentChange );

		$changeMetaData = $entityChange->getMetadata();
		if ( !$changeUpdated ) {
			$this->assertSame( [], $changeMetaData );
		} else {
			$this->assertSame( $recentChangeAttrs['rc_timestamp'], $entityChange->getTime() );
			$this->assertSame( $recentChangeAttrs['rc_bot'], $changeMetaData['bot'] );
			$this->assertSame( $recentChangeAttrs['rc_cur_id'], $changeMetaData['page_id'] );
			$this->assertSame( $recentChangeAttrs['rc_this_oldid'], $changeMetaData['rev_id'] );
			$this->assertSame( $recentChangeAttrs['rc_last_oldid'], $changeMetaData['parent_id'] );
			$this->assertSame( $recentChangeAttrs['rc_comment'], $changeMetaData['comment'] );
		}
	}

	private function newHookHandler(): RecentChangeSaveHookHandler {
		return new RecentChangeSaveHookHandler(
			$this->changeStore,
			$this->changeHolder,
			$this->subscriptionLookup,
			$this->centralIdLookup
		);
	}

	private function newStubRecentChangeWithAttributes( array $attributes ): RecentChange {
		$rc = $this->createStub( RecentChange::class );
		$rc->method( 'getAttribute' )
			->willReturnCallback( function ( $key ) use ( $attributes ) {
				return $attributes[$key] ?? null;
			} );

		return $rc;
	}

	private function newEntityChange(): EntityChange {
		return new EntityChange( [ 'type' => 'wikibase-someEntity~update', 'object_id' => 'Q1' ] );
	}

}

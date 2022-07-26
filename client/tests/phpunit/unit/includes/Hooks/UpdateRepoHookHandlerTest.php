<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Hooks;

use IJobSpecification;
use JobQueue;
use JobQueueGroup;
use MediaWiki\Revision\RevisionRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Title;
use User;
use Wikibase\Client\Hooks\UpdateRepoHookHandler;
use Wikibase\Client\NamespaceChecker;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ReplicationWaiter;
use Wikibase\Lib\Store\SiteLinkLookup;
use WikiPage;

/**
 * @covers \Wikibase\Client\Hooks\UpdateRepoHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoHookHandlerTest extends TestCase {

	public function doArticleDeleteCompleteProvider() {
		yield 'Success' => [
			'expectsSuccess' => true,
			'propagateChangesToRepo' => true,
			'itemId' => new ItemId( 'Q42' ),
		];
		yield 'propagateChangesToRepo set to false' => [
			'expectsSuccess' => false,
			'propagateChangesToRepo' => false,
			'itemId' => new ItemId( 'Q42' ),
		];
		yield 'Not connected to an item' => [
			'expectsSuccess' => false,
			'propagateChangesToRepo' => true,
			'itemId' => null,
		];
	}

	/**
	 * @dataProvider doArticleDeleteCompleteProvider
	 */
	public function testDoArticleDeleteComplete(
		bool $expectsSuccess,
		bool $propagateChangesToRepo,
		?ItemId $itemId
	): void {
		$handler = $this->newUpdateRepoHookHandlers(
			true,
			$expectsSuccess ? 'UpdateRepoOnDelete' : null,
			$propagateChangesToRepo,
			$itemId
		);
		$title = $this->getTitle();

		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTitle' )
			->willReturn( $title );

		$this->assertTrue(
			$handler->onArticleDeleteComplete( $wikiPage, $this->createMock( User::class ),
				null, null, null, null, null )
		);

		$this->assertSame(
			$expectsSuccess,
			isset( $title->wikibasePushedDeleteToRepo ) && $title->wikibasePushedDeleteToRepo,
			'Delete got propagated to repo.'
		);
	}

	public function doPageMoveCompleteProvider() {
		yield 'Regular move with redirect' => [
			'jobName' => 'UpdateRepoOnMove',
			'isWikibaseEnabled' => true,
			'propagateChangesToRepo' => true,
			'itemId' => new ItemId( 'Q42' ),
			'redirectExists' => true,
		];
		yield 'Move without redirect' => [
			'jobName' => 'UpdateRepoOnMove',
			'isWikibaseEnabled' => true,
			'propagateChangesToRepo' => true,
			'itemId' => new ItemId( 'Q42' ),
			'redirectExists' => false,
		];
		yield 'Moved into non-Wikibase NS with redirect' => [
			'jobName' => null,
			'isWikibaseEnabled' => false,
			'propagateChangesToRepo' => true,
			'itemId' => new ItemId( 'Q42' ),
			'redirectExists' => true,
		];
		yield 'Moved into non-Wikibase NS without redirect' => [
			'jobName' => 'UpdateRepoOnDelete',
			'isWikibaseEnabled' => false,
			'propagateChangesToRepo' => true,
			'itemId' => new ItemId( 'Q42' ),
			'redirectExists' => false,
		];
		yield 'propagateChangesToRepo set to false' => [
			'jobName' => null,
			'isWikibaseEnabled' => true,
			'propagateChangesToRepo' => false,
			'itemId' => new ItemId( 'Q42' ),
			'redirectExists' => true,
		];
		yield 'Not connected to an item' => [
			'jobName' => null,
			'isWikibaseEnabled' => true,
			'propagateChangesToRepo' => false,
			'itemId' => null,
			'redirectExists' => true,
		];
	}

	/**
	 * @dataProvider doPageMoveCompleteProvider
	 */
	public function testDoPageMoveComplete(
		?string $jobName,
		bool $isWikibaseEnabled,
		bool $propagateChangesToRepo,
		?ItemId $itemId,
		bool $redirectExists
	): void {
		$handler = $this->newUpdateRepoHookHandlers(
			$isWikibaseEnabled,
			$jobName,
			$propagateChangesToRepo,
			$itemId
		);
		$oldTitle = $this->getTitle();
		$newTitle = $this->getTitle();

		$this->assertTrue(
			$handler->onPageMoveComplete(
				$oldTitle,
				$newTitle,
				$this->createMock( User::class ),
				0,
				$redirectExists ? 1 : 0,
				'',
				$this->createMock( RevisionRecord::class )
			)
		);
	}

	private function getTitle(): Title {
		// get a Title mock with all methods mocked except the magics __get and __set to
		// allow the DeprecationHelper trait methods to work and handle non-existing class variables
		// correctly, see UpdateRepoHookHandlers.php:doArticleDeleteComplete
		$title = $this->createPartialMock(
			Title::class,
			array_diff( get_class_methods( Title::class ), [ '__get', '__set' ] )
		);
		$title->method( 'getPrefixedText' )
			->willReturn( 'UpdateRepoHookHandlersTest' );

		return $title;
	}

	private function newUpdateRepoHookHandlers(
		bool $isWikibaseEnabled,
		?string $jobName,
		bool $propagateChangesToRepo,
		?ItemId $itemId
	): UpdateRepoHookHandler {
		$namespaceChecker = $this->createMock( NamespaceChecker::class );
		$namespaceChecker->method( 'isWikibaseEnabled' )
			->willReturn( $isWikibaseEnabled );

		$jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'supportsDelayedJobs' ] )
			->getMockForAbstractClass();
		$jobQueue->method( 'supportsDelayedJobs' )
			->willReturn( true );

		$jobQueueGroup = $this->createMock( JobQueueGroup::class );
		if ( $jobName !== null ) {
			$jobQueueGroup->expects( $this->once() )
				->method( 'push' )
				->with( $this->isInstanceOf( IJobSpecification::class ) );
			$jobQueueGroup->expects( $this->once() )
				->method( 'get' )
				->with( $jobName )
				->willReturn( $jobQueue );
		} else {
			$jobQueueGroup->expects( $this->never() )
				->method( 'push' );
			$jobQueueGroup->expects( $this->never() )
				->method( 'get' );
		}

		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup->method( 'getItemIdForLink' )
			->with( 'clientwiki', 'UpdateRepoHookHandlersTest' )
			->willReturn( $itemId );

		$replicationWaiter = $this->createMock( ReplicationWaiter::class );
		$replicationWaiter->method( 'getMaxLag' )
			->willReturn( [ '', -1, 0 ] );

		$clientDb = $this->createMock( ClientDomainDb::class );
		$clientDb->method( 'replication' )
			->willReturn( $replicationWaiter );

		return new UpdateRepoHookHandler(
			$namespaceChecker,
			$jobQueueGroup,
			$siteLinkLookup,
			new NullLogger(),
			$clientDb,
			'clientwiki',
			$propagateChangesToRepo
		);
	}

}

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
		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
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

		$this->assertSame(
			$jobName !== null,
			isset( $newTitle->wikibasePushedMoveToRepo ) && $newTitle->wikibasePushedMoveToRepo,
			'Move got propagated to repo.'
		);

		$this->assertFalse( property_exists( $oldTitle, 'wikibasePushedMoveToRepo' ),
			'Should not touch $oldTitle' );
	}

	private function getTitle(): Title {
		// get a Title mock with all methods mocked except the magics __get and __set to
		// allow the DeprecationHelper trait methods to work and handle non-existing class variables
		// correctly, see UpdateRepoHookHandlers.php:doArticleDeleteComplete
		$title = $this->createPartialMock(
			Title::class,
			array_diff( get_class_methods( Title::class ), [ '__get', '__set' ] )
		);
		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'UpdateRepoHookHandlersTest' ) );

		return $title;
	}

	private function newUpdateRepoHookHandlers(
		bool $isWikibaseEnabled,
		?string $jobName,
		bool $propagateChangesToRepo,
		?ItemId $itemId
	): UpdateRepoHookHandler {
		$namespaceChecker = $this->getMockBuilder( NamespaceChecker::class )
			->disableOriginalConstructor()
			->getMock();
		$namespaceChecker->expects( $this->any() )
			->method( 'isWikibaseEnabled' )
			->will( $this->returnValue( $isWikibaseEnabled ) );

		$jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'supportsDelayedJobs' ] )
			->getMockForAbstractClass();
		$jobQueue->expects( $this->any() )
			->method( 'supportsDelayedJobs' )
			->will( $this->returnValue( true ) );

		$jobQueueGroup = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->getMock();
		if ( $jobName !== null ) {
			$jobQueueGroup->expects( $this->once() )
				->method( 'push' )
				->with( $this->isInstanceOf( IJobSpecification::class ) );
			$jobQueueGroup->expects( $this->once() )
				->method( 'get' )
				->with( $jobName )
				->will( $this->returnValue( $jobQueue ) );
		} else {
			$jobQueueGroup->expects( $this->never() )
				->method( 'push' );
			$jobQueueGroup->expects( $this->never() )
				->method( 'get' );
		}

		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->with( 'clientwiki', 'UpdateRepoHookHandlersTest' )
			->will( $this->returnValue( $itemId ) );

		return new UpdateRepoHookHandler(
			$namespaceChecker,
			$jobQueueGroup,
			$siteLinkLookup,
			new NullLogger(),
			'repowiki',
			'clientwiki',
			$propagateChangesToRepo
		);
	}

}

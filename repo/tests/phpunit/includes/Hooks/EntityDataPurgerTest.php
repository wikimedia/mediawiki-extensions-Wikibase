<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Hooks;

use HtmlCacheUpdater;
use IJobSpecification;
use JobQueueGroup;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Hooks\EntityDataPurger;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use WikiPage;

/**
 * @covers \Wikibase\Repo\Hooks\EntityDataPurger
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityDataPurgerTest extends TestCase {

	private function mockJobQueueGroupNoop(): JobQueueGroup {
		$jobQueueGroup = $this->createMock( JobQueueGroup::class );
		$jobQueueGroup->expects( $this->never() )
			->method( 'push' );
		return $jobQueueGroup;
	}

	public function testGivenEntityIdLookupReturnsNull_handlerDoesNothing() {
		$title = Title::makeTitle( NS_PROJECT, 'About' );
		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $title )
			->willReturn( null );
		$entityDataUriManager = $this->createMock( EntityDataUriManager::class );
		$entityDataUriManager->expects( $this->never() )
			->method( 'getPotentiallyCachedUrls' );
		$htmlCacheUpdater = $this->createMock( HtmlCacheUpdater::class );
		$htmlCacheUpdater->expects( $this->never() )
			->method( 'purgeUrls' );
		$purger = new EntityDataPurger(
			$entityIdLookup,
			$entityDataUriManager,
			$htmlCacheUpdater,
			$this->mockJobQueueGroupNoop()
		);

		$purger->onArticleRevisionVisibilitySet( $title, [ 1, 2, 3 ], [] );
	}

	public function testGivenEntityIdLookupReturnsId_handlerPurgesCache() {
		$title = Title::newFromTextThrow( 'Item:Q1' );
		$entityId = new ItemId( 'Q1' );
		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $title )
			->willReturn( $entityId );
		$entityDataUriManager = $this->createMock( EntityDataUriManager::class );
		$entityDataUriManager->expects( $this->once() )
			->method( 'getPotentiallyCachedUrls' )
			->with( $entityId, 1 )
			->willReturn( [ 'urlA/Q1/1', 'urlB/Q1/1' ] );
		$htmlCacheUpdater = $this->createMock( HtmlCacheUpdater::class );
		$htmlCacheUpdater->expects( $this->once() )
			->method( 'purgeUrls' )
			->with( [ 'urlA/Q1/1', 'urlB/Q1/1' ] );
		$purger = new EntityDataPurger(
			$entityIdLookup,
			$entityDataUriManager,
			$htmlCacheUpdater,
			$this->mockJobQueueGroupNoop()
		);

		$purger->onArticleRevisionVisibilitySet( $title, [ 1 ], [] );
	}

	public function testGivenMultipleRevisions_handlerPurgesCacheOnce() {
		$title = Title::newFromTextThrow( 'Item:Q1' );
		$entityId = new ItemId( 'Q1' );
		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $title )
			->willReturn( $entityId );
		$entityDataUriManager = $this->createMock( EntityDataUriManager::class );
		$entityDataUriManager
			->method( 'getPotentiallyCachedUrls' )
			->withConsecutive(
				[ $entityId, 1 ],
				[ $entityId, 2 ],
				[ $entityId, 3 ]
			)
			->willReturnOnConsecutiveCalls(
				[ 'urlA/Q1/1', 'urlB/Q1/1' ],
				[ 'urlA/Q1/2', 'urlB/Q1/2' ],
				[ 'urlA/Q1/3', 'urlB/Q1/3' ]
			);
		$htmlCacheUpdater = $this->createMock( HtmlCacheUpdater::class );
		$htmlCacheUpdater->expects( $this->once() )
			->method( 'purgeUrls' )
			->with( [
				'urlA/Q1/1', 'urlB/Q1/1',
				'urlA/Q1/2', 'urlB/Q1/2',
				'urlA/Q1/3', 'urlB/Q1/3',
			] );
		$purger = new EntityDataPurger(
			$entityIdLookup,
			$entityDataUriManager,
			$htmlCacheUpdater,
			$this->mockJobQueueGroupNoop()
		);

		$purger->onArticleRevisionVisibilitySet( $title, [ 1, 2, 3 ], [] );
	}

	public function testDeletionHandlerPushesJob() {
		$title = Title::makeTitle( 0, 'Q123' );
		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTitle' )
			->willReturn( $title );

		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->method( 'getEntityIdForTitle' )
			->with( $title )
			->willReturn( new ItemId( 'Q123' ) );
		$entityDataUriManager = $this->createMock( EntityDataUriManager::class );
		$entityDataUriManager->expects( $this->never() )
			->method( 'getPotentiallyCachedUrls' );
		$htmlCacheUpdater = $this->createMock( HtmlCacheUpdater::class );
		$htmlCacheUpdater->expects( $this->never() )
			->method( 'purgeUrls' );

		$jobQueueGroup = $this->createMock( JobQueueGroup::class );
		$jobQueueGroup->expects( $this->once() )
			->method( 'lazyPush' )
			->with( $this->callback( function ( IJobSpecification $specification ) {
				$this->assertSame( 'PurgeEntityData', $specification->getType() );
				$expectedParams = [
					'namespace' => 0,
					'title' => 'Q123',
					'pageId' => 123,
					'entityId' => 'Q123',
				];
				$actualParams = $specification->getParams();
				ksort( $expectedParams );
				ksort( $actualParams );
				$this->assertSame( $expectedParams, $actualParams );
				return true;
			} ) );

		$purger = new EntityDataPurger(
			$entityIdLookup,
			$entityDataUriManager,
			$htmlCacheUpdater,
			$jobQueueGroup
		);

		$purger->onArticleDeleteComplete(
			$wikiPage,
			// unused
			null, null,
			123,
			// unused
			null, null, null
		);
	}
}

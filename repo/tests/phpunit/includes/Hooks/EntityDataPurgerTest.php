<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Hooks;

use HtmlCacheUpdater;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Hooks\EntityDataPurger;
use Wikibase\Repo\LinkedData\EntityDataUriManager;

/**
 * @covers \Wikibase\Repo\Hooks\EntityDataPurger
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityDataPurgerTest extends TestCase {

	public function testGivenEntityIdLookupReturnsNull_handlerDoesNothing() {
		$title = Title::newFromText( 'Project:About' );
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
		$purger = new EntityDataPurger( $entityIdLookup, $entityDataUriManager, $htmlCacheUpdater );

		$purger->onArticleRevisionVisibilitySet( $title, [ 1, 2, 3 ], [] );
	}

	public function testGivenEntityIdLookupReturnsId_handlerPurgesCache() {
		$title = Title::newFromText( 'Item:Q1' );
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
		$purger = new EntityDataPurger( $entityIdLookup, $entityDataUriManager, $htmlCacheUpdater );

		$purger->onArticleRevisionVisibilitySet( $title, [ 1 ], [] );
	}

	public function testGivenMultipleRevisions_handlerPurgesCacheOnce() {
		$title = Title::newFromText( 'Item:Q1' );
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
		$purger = new EntityDataPurger( $entityIdLookup, $entityDataUriManager, $htmlCacheUpdater );

		$purger->onArticleRevisionVisibilitySet( $title, [ 1, 2, 3 ], [] );
	}
}

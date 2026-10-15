<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\RateLimitReached;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityIdAllocator;
use Wikibase\Repo\EditEntity\EditEntityStatus;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityIdAllocator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdAllocatorTest extends TestCase {

	public function testAllocateId(): void {
		$item = new Item();
		$expectedId = new ItemId( 'Q123' );

		$entityStore = $this->createMock( EntityStore::class );
		$entityStore->expects( $this->once() )
			->method( 'assignFreshId' )
			->with( $item )
			->willReturnCallback( fn( Item $item ) => $item->setId( $expectedId ) );

		( new EntityIdAllocator( $entityStore ) )->allocateId( $item );

		$this->assertEquals( $expectedId, $item->getId() );
	}

	public function testGivenRateLimitReached_throws(): void {
		$entityStore = $this->createStub( EntityStore::class );
		$entityStore->method( 'assignFreshId' )
			->willThrowException( new StorageException( EditEntityStatus::newFatal( 'actionthrottledtext' ) ) );

		$this->expectException( RateLimitReached::class );

		( new EntityIdAllocator( $entityStore ) )->allocateId( new Item() );
	}

	public function testGivenOtherStorageException_rethrows(): void {
		$exception = new StorageException( EditEntityStatus::newFatal( 'something-else-went-wrong' ) );
		$entityStore = $this->createStub( EntityStore::class );
		$entityStore->method( 'assignFreshId' )->willThrowException( $exception );

		try {
			( new EntityIdAllocator( $entityStore ) )->allocateId( new Item() );
			$this->fail( 'expected exception was not thrown' );
		} catch ( StorageException $e ) {
			$this->assertSame( $exception, $e );
		}
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItemsRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItemsResponse;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Aliases;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Descriptions;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Sitelinks;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statements;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\QueryContext;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemResolverTest extends TestCase {

	public function testResolveItems(): void {
		$context = new QueryContext();
		$requestedItems = [ 'Q123', 'Q321', 'Q234' ];
		$itemsBatch = $this->newItemsBatchForIds( $requestedItems );

		$batchGetItems = $this->createMock( BatchGetItems::class );
		// expecting the use case to only be called once demonstrates that the resolver aggregates multiple requests into one batch
		$batchGetItems->expects( $this->once() )
			->method( 'execute' )
			->with( new BatchGetItemsRequest( $requestedItems ) )
			->willReturn( new BatchGetItemsResponse( $itemsBatch ) );

		$resolver = new ItemResolver( $batchGetItems );

		$item1Promise = $resolver->resolveItem( $requestedItems[0], $context );
		$item2Promise = $resolver->resolveItem( $requestedItems[1], $context );
		$item3Promise = $resolver->resolveItem( $requestedItems[2], $context );

		SyncPromiseQueue::run(); // resolves the three promises above

		$this->assertEquals( $itemsBatch->getItem( new ItemId( $requestedItems[0] ) ), $item1Promise->result );
		$this->assertEquals( $itemsBatch->getItem( new ItemId( $requestedItems[1] ) ), $item2Promise->result );
		$this->assertEquals( $itemsBatch->getItem( new ItemId( $requestedItems[2] ) ), $item3Promise->result );
	}

	public function testGivenRequestedItemDoesNotExist_throwsItemNotFound(): void {
		$requestedItem = 'Q99999';
		$batchGetItems = $this->createStub( BatchGetItems::class );
		$batchGetItems->expects( $this->once() )
			->method( 'execute' )
			->willReturn( new BatchGetItemsResponse( new ItemsBatch( [ $requestedItem => null ] ) ) );

		$resolver = new ItemResolver( $batchGetItems );

		$promise = $resolver->resolveItem( $requestedItem, new QueryContext() );
		SyncPromiseQueue::run(); // resolves the promise above

		$this->assertInstanceOf( GraphQLError::class, $promise->result );
		$this->assertSame( "Item \"$requestedItem\" does not exist.", $promise->result->getMessage() );
	}

	private function newItemsBatchForIds( array $itemIds ): ItemsBatch {
		$batch = [];
		foreach ( $itemIds as $id ) {
			$batch[$id] = new Item(
				new ItemId( $id ),
				new Labels(),
				new Descriptions(),
				new Aliases(),
				new Sitelinks(),
				new Statements(),
			);
		}

		return new ItemsBatch( $batch );
	}
}

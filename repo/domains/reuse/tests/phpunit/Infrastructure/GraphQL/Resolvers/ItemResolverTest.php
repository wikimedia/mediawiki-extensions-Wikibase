<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\GraphQL;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItemsRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItemsResponse;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemResolverTest extends TestCase {
	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	public function testResolve(): void {
		$requestedItems = [ 'Q123', 'Q321', 'Q234' ];

		$batchGetItems = $this->createMock( BatchGetItems::class );
		// expecting the use case to only be called once demonstrates that the resolver aggregates multiple requests into one batch
		$batchGetItems->expects( $this->once() )
			->method( 'execute' )
			->with( new BatchGetItemsRequest( $requestedItems ) )
			->willReturn( new BatchGetItemsResponse( $this->newItemsBatchForIds( $requestedItems ) ) );

		$resolver = new ItemResolver( $batchGetItems );

		$item1Promise = $resolver->resolve( $requestedItems[0] );
		$item2Promise = $resolver->resolve( $requestedItems[1] );
		$item3Promise = $resolver->resolve( $requestedItems[2] );

		SyncPromise::runQueue(); // resolves the three promises above

		$this->assertEquals( [ 'id' => $requestedItems[0] ], $item1Promise->result );
		$this->assertEquals( [ 'id' => $requestedItems[1] ], $item2Promise->result );
		$this->assertEquals( [ 'id' => $requestedItems[2] ], $item3Promise->result );
	}

	private function newItemsBatchForIds( array $itemIds ): ItemsBatch {
		$batch = [];
		foreach ( $itemIds as $id ) {
			$batch[$id] = new Item( new ItemId( $id ) );
		}

		return new ItemsBatch( $batch );
	}
}

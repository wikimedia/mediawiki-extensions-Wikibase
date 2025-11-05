<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Application\UseCases\BatchGetItems;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItemsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemsBatchRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BatchGetItemsTest extends TestCase {
	public function testBatchGetItems(): void {
		$requestedIds = [ 'Q123', 'Q321' ];
		$itemsBatch = $this->createStub( ItemsBatch::class );

		$retriever = $this->createMock( ItemsBatchRetriever::class );
		$retriever->expects( $this->once() )
			->method( 'getItems' )
			->with( ...$requestedIds )
			->willReturn( $itemsBatch );

		$this->assertSame(
			$itemsBatch,
			( new BatchGetItems( $retriever ) )
				->execute( new BatchGetItemsRequest( $requestedIds ) )->itemsBatch
		);
	}
}

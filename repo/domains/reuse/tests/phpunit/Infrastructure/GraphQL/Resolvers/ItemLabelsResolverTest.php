<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabelsRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabelsResponse;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsResolver;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemLabelsResolverTest extends TestCase {

	public function testResolve(): void {
		$requestedItems = [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ];
		$requestedItemIdSerializations = array_map( fn( $id ) => (string)$id, $requestedItems );
		$requestedLanguages = [ 'de', 'en' ];
		$itemLabelsBatch = $this->newLabelsBatch( $requestedItems, $requestedLanguages );

		$batchGetItemLabels = $this->createMock( BatchGetItemLabels::class );
		// expecting the use case to only be called once demonstrates that the resolver aggregates multiple requests into one batch
		$batchGetItemLabels->expects( $this->once() )
			->method( 'execute' )
			->with( new BatchGetItemLabelsRequest( $requestedItemIdSerializations, $requestedLanguages ) )
			->willReturn( new BatchGetItemLabelsResponse( $itemLabelsBatch ) );

		$resolver = new ItemLabelsResolver( $batchGetItemLabels );

		$promise1 = $resolver->resolve( $requestedItems[0], $requestedLanguages[0] );
		$promise2 = $resolver->resolve( $requestedItems[0], $requestedLanguages[1] );
		$promise3 = $resolver->resolve( $requestedItems[1], $requestedLanguages[0] );
		$promise4 = $resolver->resolve( $requestedItems[1], $requestedLanguages[1] );

		SyncPromiseQueue::run(); // resolves the promises above

		$this->assertSame(
			$itemLabelsBatch->getItemLabels( $requestedItems[0] )
				->getLabelInLanguage( $requestedLanguages[0] )
				->text,
			$promise1->result
		);
		$this->assertSame(
			$itemLabelsBatch->getItemLabels( $requestedItems[0] )
				->getLabelInLanguage( $requestedLanguages[1] )
				->text,
			$promise2->result
		);
		$this->assertSame(
			$itemLabelsBatch->getItemLabels( $requestedItems[1] )
				->getLabelInLanguage( $requestedLanguages[0] )
				->text,
			$promise3->result
		);
		$this->assertSame(
			$itemLabelsBatch->getItemLabels( $requestedItems[1] )
				->getLabelInLanguage( $requestedLanguages[1] )
				->text,
			$promise4->result
		);
	}

	private function newLabelsBatch( array $itemIds, array $languageCodes ): ItemLabelsBatch {
		$batch = [];
		foreach ( $itemIds as $id ) {
			$labels = [];
			foreach ( $languageCodes as $languageCode ) {
				$labels[] = new Label( $languageCode, "$languageCode label " . rand() );
			}

			$batch[$id->getSerialization()] = new Labels( ...$labels );
		}

		return new ItemLabelsBatch( $batch );
	}
}

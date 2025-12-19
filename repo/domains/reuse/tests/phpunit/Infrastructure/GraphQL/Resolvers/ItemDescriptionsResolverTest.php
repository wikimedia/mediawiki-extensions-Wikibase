<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;
use GraphQL\GraphQL;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptions;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptionsRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptionsResponse;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Description;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Descriptions;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemDescriptionsBatch;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemDescriptionsResolver;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemDescriptionsResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemDescriptionsResolverTest extends TestCase {
	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	public function testResolve(): void {
		$requestedItems = [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ];
		$requestedItemIdSerializations = array_map( fn( $id ) => (string)$id, $requestedItems );
		$requestedLanguages = [ 'de', 'en' ];
		$itemDescriptionsBatch = $this->newDescriptionsBatch( $requestedItems, $requestedLanguages );

		$batchGetItemDescriptions = $this->createMock( BatchGetItemDescriptions::class );
		// expecting the use case to only be called once demonstrates that the resolver aggregates multiple requests into one batch
		$batchGetItemDescriptions->expects( $this->once() )
			->method( 'execute' )
			->with( new BatchGetItemDescriptionsRequest( $requestedItemIdSerializations, $requestedLanguages ) )
			->willReturn( new BatchGetItemDescriptionsResponse( $itemDescriptionsBatch ) );

		$resolver = new ItemDescriptionsResolver( $batchGetItemDescriptions );

		$promise1 = $resolver->resolve( $requestedItems[0], $requestedLanguages[0] );
		$promise2 = $resolver->resolve( $requestedItems[0], $requestedLanguages[1] );
		$promise3 = $resolver->resolve( $requestedItems[1], $requestedLanguages[0] );
		$promise4 = $resolver->resolve( $requestedItems[1], $requestedLanguages[1] );

		SyncPromiseQueue::run(); // resolves the promises above

		$this->assertSame(
			$itemDescriptionsBatch->getItemDescriptions( $requestedItems[0] )
				->getDescriptionInLanguage( $requestedLanguages[0] )
				->text,
			$promise1->result
		);
		$this->assertSame(
			$itemDescriptionsBatch->getItemDescriptions( $requestedItems[0] )
				->getDescriptionInLanguage( $requestedLanguages[1] )
				->text,
			$promise2->result
		);
		$this->assertSame(
			$itemDescriptionsBatch->getItemDescriptions( $requestedItems[1] )
				->getDescriptionInLanguage( $requestedLanguages[0] )
				->text,
			$promise3->result
		);
		$this->assertSame(
			$itemDescriptionsBatch->getItemDescriptions( $requestedItems[1] )
				->getDescriptionInLanguage( $requestedLanguages[1] )
				->text,
			$promise4->result
		);
	}

	private function newDescriptionsBatch( array $itemIds, array $languageCodes ): ItemDescriptionsBatch {
		$batch = [];
		foreach ( $itemIds as $id ) {
			$descriptions = [];
			foreach ( $languageCodes as $languageCode ) {
				$descriptions[] = new Description( $languageCode, "$languageCode description " . rand() );
			}

			$batch[$id->getSerialization()] = new Descriptions( ...$descriptions );
		}

		return new ItemDescriptionsBatch( $batch );
	}
}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabelsRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabelsResponse;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsResolver;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyLabelsResolverTest extends TestCase {

	public function testResolve(): void {
		$requestedProperties = [ new NumericPropertyId( 'P123' ), new NumericPropertyId( 'P321' ) ];
		$requestedPropertyIdSerializations = array_map( fn( $id ) => (string)$id, $requestedProperties );
		$requestedLanguages = [ 'de', 'en' ];
		$propertyLabelsBatch = $this->newLabelsBatch( $requestedProperties, $requestedLanguages );

		$batchGetPropertyLabels = $this->createMock( BatchGetPropertyLabels::class );
		// expecting the use case to only be called once demonstrates that the resolver aggregates multiple requests into one batch
		$batchGetPropertyLabels->expects( $this->once() )
			->method( 'execute' )
			->with( new BatchGetPropertyLabelsRequest( $requestedPropertyIdSerializations, $requestedLanguages ) )
			->willReturn( new BatchGetPropertyLabelsResponse( $propertyLabelsBatch ) );

		$resolver = new PropertyLabelsResolver( $batchGetPropertyLabels );

		$promise1 = $resolver->resolve( $requestedProperties[0], $requestedLanguages[0] );
		$promise2 = $resolver->resolve( $requestedProperties[0], $requestedLanguages[1] );
		$promise3 = $resolver->resolve( $requestedProperties[1], $requestedLanguages[0] );
		$promise4 = $resolver->resolve( $requestedProperties[1], $requestedLanguages[1] );

		SyncPromiseQueue::run(); // resolves the promises above

		$this->assertSame(
			$propertyLabelsBatch->getPropertyLabels( $requestedProperties[0] )
				->getLabelInLanguage( $requestedLanguages[0] )
				->text,
			$promise1->result
		);
		$this->assertSame(
			$propertyLabelsBatch->getPropertyLabels( $requestedProperties[0] )
				->getLabelInLanguage( $requestedLanguages[1] )
				->text,
			$promise2->result
		);
		$this->assertSame(
			$propertyLabelsBatch->getPropertyLabels( $requestedProperties[1] )
				->getLabelInLanguage( $requestedLanguages[0] )
				->text,
			$promise3->result
		);
		$this->assertSame(
			$propertyLabelsBatch->getPropertyLabels( $requestedProperties[1] )
				->getLabelInLanguage( $requestedLanguages[1] )
				->text,
			$promise4->result
		);
	}

	private function newLabelsBatch( array $propertyIds, array $languageCodes ): PropertyLabelsBatch {
		$batch = [];
		foreach ( $propertyIds as $id ) {
			$labels = [];
			foreach ( $languageCodes as $languageCode ) {
				$labels[] = new Label( $languageCode, "$languageCode label " . rand() );
			}

			$batch[$id->getSerialization()] = new Labels( ...$labels );
		}

		return new PropertyLabelsBatch( $batch );
	}
}

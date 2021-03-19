<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataAccess\Tests\FakePrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupTest extends ServiceWiringTestCase {

	private function getPrefetchCallbackDefinitions( array $definedPrefetchers ): array {
		return array_fill_keys(
			$definedPrefetchers,
			[
				EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK => function () {
					return new FakePrefetchingTermLookup();
				}
			]
		);
	}

	private function getEntitySourceDefinitions( array $entitySourceToTypes ): array {
		return array_map(
			function (
				string $sourceName,
				array $entityTypes
			): EntitySource {
				return new EntitySource(
					$sourceName,
					false,
					array_fill_keys( $entityTypes, [
						'namespaceId' => 1,
						'slot' => 'main'
					] ),
					'',
					'',
					'',
					''
				);
			},
			array_keys( $entitySourceToTypes ),
			$entitySourceToTypes
		);
	}

	private function mockServices(
		array $prefetchCallbackDefinitions,
		array $entitySourceDefinitions
	): void {
		$mockEntityTypeDefinitions = new EntityTypeDefinitions( $prefetchCallbackDefinitions );

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$mockEntityTypeDefinitions
		);

		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions(
				$entitySourceDefinitions,
				$mockEntityTypeDefinitions
			)
		);

		$this->mockService( 'WikibaseRepo.SingleEntitySourceServicesFactory',
			function ( EntitySource $source ) use ( $entitySourceDefinitions ) {
				$this->assertContains( $source, $entitySourceDefinitions );

				return $this->createMock( SingleEntitySourceServices::class );
			}
		);
	}

	public function testConstruction(): void {
		$this->mockServices( [], [] );

		$this->assertInstanceOf(
			PrefetchingTermLookup::class,
			$this->getService( 'WikibaseRepo.PrefetchingTermLookup' )
		);
	}

	public function testReturnsTermsOfEntitiesConfiguredInSources(): void {
		$this->mockServices(
			$this->getPrefetchCallbackDefinitions( [ 'item' ] ),
			$this->getEntitySourceDefinitions( [
				'test-source' => [ 'item' ]
			] )
		);

		$itemId = new ItemId( 'Q200' );
		$fakeItemLabel = 'Q200 en label';

		/** @var PrefetchingTermLookup $lookup */
		$lookup = $this->getService( 'WikibaseRepo.PrefetchingTermLookup' );
		$this->assertEquals( $fakeItemLabel, $lookup->getLabel( $itemId, 'en' ) );
	}

	public function testReturnsNullWhenGivenEntitiesUnknownInSources(): void {
		$this->mockServices(
			$this->getPrefetchCallbackDefinitions( [ 'something' ] ),
			$this->getEntitySourceDefinitions( [
				'test-source' => [ 'something' ]
			] )
		);

		/** @var PrefetchingTermLookup $lookup */
		$lookup = $this->getService( 'WikibaseRepo.PrefetchingTermLookup' );
		$this->assertNull( $lookup->getLabel( new ItemId( 'Q200' ), 'en' ) );
	}

	public function testBuffersDataOfSourceEntities(): void {
		$this->mockServices(
			$this->getPrefetchCallbackDefinitions( [ 'item', 'property' ] ),
			$this->getEntitySourceDefinitions( [
				'some-source' => [ 'item' ],
				'another-source' => [ 'property' ]
			] )
		);

		$itemId = new ItemId( 'Q200' );
		$propertyId = new PropertyId( 'P500' );
		$fakeItemLabel = 'Q200 en label';
		$fakePropertyLabel = 'P500 en label';

		/** @var PrefetchingTermLookup $lookup */
		$lookup = $this->getService( 'WikibaseRepo.PrefetchingTermLookup' );
		$lookup->prefetchTerms( [ $itemId, $propertyId ], [ 'label' ], [ 'en' ] );
		$this->assertEquals( $fakeItemLabel, $lookup->getPrefetchedTerm( $itemId, 'label', 'en' ) );
		$this->assertEquals( $fakePropertyLabel, $lookup->getPrefetchedTerm( $propertyId, 'label', 'en' ) );
	}
}

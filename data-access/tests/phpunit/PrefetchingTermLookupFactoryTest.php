<?php

declare( strict_types=1 );

namespace Wikibase\DataAccess\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\PrefetchingTermLookupFactory;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikimedia\Assert\ParameterAssertionException;
use Wikimedia\Assert\PostconditionException;
use Wikimedia\Assert\PreconditionException;

/**
 * @license GPL-2.0-or-later
 * @group Wikibase
 * @covers \Wikibase\DataAccess\PrefetchingTermLookupFactory
 */
class PrefetchingTermLookupFactoryTest extends TestCase {

	public function testGetLookupForType(): void {
		$mockLookup = $this->createMock( PrefetchingTermLookup::class );
		$entityTypeDefinitions = $this->getPrefetchCallbackDefinitions( [
			'something' => $mockLookup
		] );
		$entitySourceDefinitions = $this->getEntitySourceDefinitions( [
			'test' => [ 'something' ]
		], $entityTypeDefinitions );

		$lookupFactory = new PrefetchingTermLookupFactory(
			$entitySourceDefinitions,
			$entityTypeDefinitions
		);

		$this->assertSame( $mockLookup, $lookupFactory->getLookupForType( 'something' ) );
	}

	public function lookupForTypeErrorProvider(): array {
		return [
			[
				[],
				[ 'test' => [ 'callbackless-thing' ] ],
				'callbackless-thing',
				ParameterAssertionException::class
			],
			[
				[ 'sourceless-thing' => $this->createMock( PrefetchingTermLookup::class ) ],
				[ 'test' => [] ],
				'sourceless-thing',
				PreconditionException::class
			],
			[
				[ 'lookupless-thing' => null ],
				[ 'test' => [ 'lookupless-thing' ] ],
				'lookupless-thing',
				PostconditionException::class
			]
		];
	}

	/**
	 * @dataProvider lookupForTypeErrorProvider
	 */
	public function testGetLookupForTypeThrows(
		$prefetchCallbackDefs,
		$entitySourceDefs,
		$type,
		$exception
	): void {
		$entityTypeDefinitions = $this->getPrefetchCallbackDefinitions( $prefetchCallbackDefs );
		$entitySourceDefinitions = $this->getEntitySourceDefinitions( $entitySourceDefs, $entityTypeDefinitions );

		$lookupFactory = new PrefetchingTermLookupFactory(
			$entitySourceDefinitions,
			$entityTypeDefinitions,
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		$this->expectException( $exception );
		$lookupFactory->getLookupForType( $type );
	}

	public function testGetLookupForSourceReturnsTermLookup(): void {
		$itemId = new ItemId( 'Q200' );
		$fakeItemLabel = 'Q200 en label';

		$lookup = $this->getFakeItemLookup();

		$this->assertEquals(
			$fakeItemLabel,
			$lookup->getLabel( $itemId, 'en' ),
			'Returns Lookup for terms of entities configured in sources'
		);
	}

	public function testGetLookupForSourceReturnsTermBuffer(): void {
		$itemId = new ItemId( 'Q200' );
		$fakeItemLabel = 'Q200 en label';

		$lookup = $this->getFakeItemLookup();

		$lookup->prefetchTerms( [ $itemId ], [ 'label' ], [ 'en' ] );
		$this->assertEquals(
			$fakeItemLabel,
			$lookup->getPrefetchedTerm( $itemId, 'label', 'en' ),
			'Returned Lookup buffers data of source entities'
		);
	}

	public function testGetLookupForSourceReturnsNullLookup(): void {
		$entityTypeDefinitions = new EntityTypeDefinitions( [] );
		$entitySource = $this->getEntitySource( 'test', [ 'something' ] );
		$entitySourceDefinitions = new EntitySourceDefinitions( [ $entitySource ], $entityTypeDefinitions );

		$lookupFactory = new PrefetchingTermLookupFactory(
			$entitySourceDefinitions,
			$entityTypeDefinitions,
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		$lookup = $lookupFactory->getLookupForSource( $entitySource );
		$this->assertNull(
			$lookup->getLabel( new ItemId( 'Q200' ), 'en' ),
			'Returned Lookup returns null when given entities unknown in sources'
		);
	}

	public function testGetLookupForSourceThrows(): void {
		$entityTypeDefinitions = new EntityTypeDefinitions( [] );
		$entitySourceDefinitions = new EntitySourceDefinitions( [], $entityTypeDefinitions );

		$lookupFactory = new PrefetchingTermLookupFactory(
			$entitySourceDefinitions,
			$entityTypeDefinitions,
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		$this->expectException( ParameterAssertionException::class );

		$lookupFactory->getLookupForSource( $this->createMock( EntitySource::class ) );
	}

	public function testGetLookupForSourceCachesBySource(): void {
		$entityTypeDefinitions = new EntityTypeDefinitions( [] );
		$entitySource = $this->getEntitySource( 'test', [ 'something' ] );
		$entitySourceDefinitions = new EntitySourceDefinitions( [ $entitySource ], $entityTypeDefinitions );

		$lookupFactory = new PrefetchingTermLookupFactory(
			$entitySourceDefinitions,
			$entityTypeDefinitions,
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		$this->assertSame(
			$lookupFactory->getLookupForSource( $entitySource ),
			$lookupFactory->getLookupForSource( $entitySource )
		);
	}

	private function getFakeItemLookup(): PrefetchingTermLookup {
		$entityTypeDefinitions = $this->getPrefetchCallbackDefinitions( [
			'item' => new FakePrefetchingTermLookup()
		] );
		$entitySource = $this->getEntitySource( 'test', [ 'item' ] );
		$entitySourceDefinitions = new EntitySourceDefinitions( [ $entitySource ], $entityTypeDefinitions );

		$lookupFactory = new PrefetchingTermLookupFactory(
			$entitySourceDefinitions,
			$entityTypeDefinitions,
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		return $lookupFactory->getLookupForSource( $entitySource );
	}

	private function getPrefetchCallbackDefinitions( array $typesToLookups ): EntityTypeDefinitions {
		return new EntityTypeDefinitions( array_map(
			function ( $lookup ): array {
				return [
					EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK => function () use ( $lookup ) {
						return $lookup;
					}
				];
			},
			$typesToLookups
		) );
	}

	private function getEntitySource(
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
	}

	private function getEntitySourceDefinitions(
		array $entitySourceToTypes,
		EntityTypeDefinitions $entityTypeDefinitions
	): EntitySourceDefinitions {
		return new EntitySourceDefinitions( array_map(
			[ $this, 'getEntitySource' ],
			array_keys( $entitySourceToTypes ),
			$entitySourceToTypes
		), $entityTypeDefinitions );
	}
}

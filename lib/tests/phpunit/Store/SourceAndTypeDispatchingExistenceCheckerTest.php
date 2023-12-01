<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\SourceAndTypeDispatchingExistenceChecker;

/**
 * @covers \Wikibase\Lib\Store\SourceAndTypeDispatchingExistenceChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingExistenceCheckerTest extends TestCase {
	/**
	 * @var MockObject|EntitySourceLookup
	 */
	private $entitySourceLookup;

	/**
	 * @var MockObject|ServiceBySourceAndTypeDispatcher
	 */
	private $serviceBySourceAndTypeDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->entitySourceLookup = $this->createStub( EntitySourceLookup::class );
		$this->serviceBySourceAndTypeDispatcher = $this->createStub( ServiceBySourceAndTypeDispatcher::class );
	}

	public function testGivenExistenceCheckerDefinedForEntitySourceAndType_usesRespectiveExistenceChecker() {
		$entityId = new NumericPropertyId( 'P321' );
		$exists = false;

		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );
		$this->serviceBySourceAndTypeDispatcher = $this->createMock( ServiceBySourceAndTypeDispatcher::class );
		$propertySourceName = 'propertySource';

		$propertyExistenceChecker = $this->createMock( EntityExistenceChecker::class );
		$propertyExistenceChecker->expects( $this->once() )
			->method( 'exists' )
			->with( $entityId )
			->willReturn( $exists );

		$this->entitySourceLookup->expects( $this->atLeastOnce() )
			->method( 'getEntitySourceById' )
			->with( $entityId )
			->willReturn( $this->newEntitySourceWithName( $propertySourceName ) );

		$this->serviceBySourceAndTypeDispatcher->expects( $this->once() )
			->method( 'getServiceForSourceAndType' )
			->with( $propertySourceName, 'property' )
			->willReturn( $propertyExistenceChecker );

		$this->assertSame( $exists, $this->newDispatchingExistenceChecker()->exists( $entityId ) );
	}

	public function testExistsBatch() {
		$itemIds = [ new ItemId( 'Q123' ), new ItemId( 'Q456' ) ];
		$propertyIds = [ new NumericPropertyId( 'P123' ), new NumericPropertyId( 'P456' ) ];

		$itemSourceName = 'itemSource';
		$propertySourceName = 'propertySource';
		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );
		$this->serviceBySourceAndTypeDispatcher = $this->createMock( ServiceBySourceAndTypeDispatcher::class );

		$expectedReturnMap = [
			[ $itemIds[0], $this->newEntitySourceWithName( $itemSourceName ) ],
			[ $itemIds[1], $this->newEntitySourceWithName( $itemSourceName ) ],
			[ $propertyIds[0], $this->newEntitySourceWithName( $propertySourceName ) ],
			[ $propertyIds[1], $this->newEntitySourceWithName( $propertySourceName ) ],
		];
		$this->entitySourceLookup
			->method( 'getEntitySourceById' )
			->willReturnCallback( function ( EntityId $id ) use ( &$expectedReturnMap ) {
				$curExpectedMap = array_shift( $expectedReturnMap );
				$this->assertSame( $curExpectedMap[0], $id );
				return $curExpectedMap[1];
			} );

		$itemChecker = $this->createMock( EntityExistenceChecker::class );
		$itemChecker->expects( $this->once() )
			->method( 'existsBatch' )
			->with( $itemIds )
			->willReturn( [ 'Q123' => true, 'Q456' => false ] );

		$propertyChecker = $this->createMock( EntityExistenceChecker::class );
		$propertyChecker->expects( $this->once() )
			->method( 'existsBatch' )
			->with( $propertyIds )
			->willReturn( [ 'P123' => true, 'P456' => false ] );

		$this->serviceBySourceAndTypeDispatcher->expects( $this->exactly( 2 ) )
			->method( 'getServiceForSourceAndType' )
			->willReturnMap( [
				[ $itemSourceName, 'item', [], $itemChecker ],
				[ $propertySourceName, 'property', [], $propertyChecker ],
			] );

		$result = $this->newDispatchingExistenceChecker()->existsBatch( array_merge( $itemIds, $propertyIds ) );

		$expected = [
			'Q123' => true,
			'Q456' => false,
			'P123' => true,
			'P456' => false,
		];
		$this->assertSame( $expected, $result );
	}

	private function newDispatchingExistenceChecker(): SourceAndTypeDispatchingExistenceChecker {
		return new SourceAndTypeDispatchingExistenceChecker(
			$this->entitySourceLookup,
			$this->serviceBySourceAndTypeDispatcher
		);
	}

	private function newEntitySourceWithName( string $name ): DatabaseEntitySource {
		return new DatabaseEntitySource(
			$name,
			false, [],
			'',
			'',
			'',
			''
		);
	}

}

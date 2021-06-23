<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\TypeDispatchingExistenceChecker;

/**
 * @covers \Wikibase\Lib\Store\TypeDispatchingExistenceChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TypeDispatchingExistenceCheckerTest extends TestCase {

	/**
	 * @var array
	 */
	private $callbacks;

	/**
	 * @var MockObject|EntityExistenceChecker
	 */
	private $defaultChecker;

	/**
	 * @var MockObject|EntitySourceLookup
	 */
	private $entitySourceLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->callbacks = [];
		$this->defaultChecker = $this->createStub( EntityExistenceChecker::class );
		$this->entitySourceLookup = $this->createStub( EntitySourceLookup::class );
	}

	public function testGivenNoExistenceCheckerDefinedForEntityType_usesDefaultChecker() {
		$entityId = new PropertyId( 'P123' );
		$exists = true;

		$this->defaultChecker = $this->createMock( EntityExistenceChecker::class );
		$this->defaultChecker->expects( $this->once() )
			->method( 'exists' )
			->with( $entityId )
			->willReturn( $exists );

		$this->callbacks = [
			'someSource' => [
				'item' => function () {
					return $this->newNeverCalledMockChecker();
				}
			],
		];

		$this->assertSame( $exists, $this->newDispatchingExistenceChecker()->exists( $entityId ) );
	}

	public function testGivenExistenceCheckerDefinedForEntitySourceAndType_usesRespectiveExistenceChecker() {
		$entityId = new PropertyId( 'P321' );
		$exists = false;

		$this->defaultChecker = $this->newNeverCalledMockChecker();

		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );
		$propertySourceName = 'propertySource';
		$this->entitySourceLookup->expects( $this->once() )
			->method( 'getEntitySourceById' )
			->with( $entityId )
			->willReturn( $this->newEntitySourceWithName( $propertySourceName ) );

		$this->callbacks = [
			$propertySourceName => [
				'property' => function () use ( $entityId, $exists ) {
					$propertyExistenceChecker = $this->createMock( EntityExistenceChecker::class );
					$propertyExistenceChecker->expects( $this->once() )
						->method( 'exists' )
						->with( $entityId )
						->willReturn( $exists );

					return $propertyExistenceChecker;
				}
			],
		];

		$this->assertSame( $exists, $this->newDispatchingExistenceChecker()->exists( $entityId ) );
	}

	private function newNeverCalledMockChecker(): EntityExistenceChecker {
		$existenceChecker = $this->createMock( EntityExistenceChecker::class );
		$existenceChecker->expects( $this->never() )->method( $this->anything() );

		return $existenceChecker;
	}

	public function testExistsBatch() {
		$itemIds = [ new ItemId( 'Q123' ), new ItemId( 'Q456' ) ];
		$propertyIds = [ new PropertyId( 'P123' ), new PropertyId( 'P456' ) ];

		$itemSourceName = 'itemSource';
		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );
		$this->entitySourceLookup
			->method( 'getEntitySourceById' )
			->willReturn( $this->newEntitySourceWithName( $itemSourceName ) );

		$itemChecker = $this->createMock( EntityExistenceChecker::class );
		$itemChecker->expects( $this->once() )
			->method( 'existsBatch' )
			->with( $itemIds )
			->willReturn( [ 'Q123' => true, 'Q456' => false ] );

		$this->defaultChecker = $this->createMock( EntityExistenceChecker::class );
		$this->defaultChecker->expects( $this->once() )
			->method( 'existsBatch' )
			->with( $propertyIds )
			->willReturn( [ 'P123' => true, 'P456' => false ] );

		$this->callbacks = [
			$itemSourceName => [
				'item' => function () use ( $itemChecker ) {
					return $itemChecker;
				}
			],
		];

		$result = $this->newDispatchingExistenceChecker()->existsBatch( array_merge( $itemIds, $propertyIds ) );

		$expected = [
			'Q123' => true,
			'Q456' => false,
			'P123' => true,
			'P456' => false,
		];
		$this->assertSame( $expected, $result );
	}

	private function newDispatchingExistenceChecker(): TypeDispatchingExistenceChecker {
		return new TypeDispatchingExistenceChecker(
			$this->callbacks,
			$this->defaultChecker,
			$this->entitySourceLookup
		);
	}

	private function newEntitySourceWithName( string $name ): EntitySource {
		return new EntitySource( $name, false, [], '', '', '', '' );
	}

}

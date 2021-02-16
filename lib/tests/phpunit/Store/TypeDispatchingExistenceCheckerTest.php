<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
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

	public function testGivenNoExistenceCheckerDefinedForEntityType_usesDefaultChecker() {
		$entityId = new PropertyId( 'P123' );
		$exists = true;

		$defaultChecker = $this->createMock( EntityExistenceChecker::class );
		$defaultChecker->expects( $this->once() )
			->method( 'exists' )
			->with( $entityId )
			->willReturn( $exists );

		$existenceChecker = new TypeDispatchingExistenceChecker(
			[ 'item' => function () {
				return $this->newNeverCalledMockChecker();
			} ],
			$defaultChecker
		);

		$this->assertSame( $exists, $existenceChecker->exists( $entityId ) );
	}

	public function testGivenExistenceCheckerDefinedForEntityType_usesRespectiveExistenceChecker() {
		$entityId = new PropertyId( 'P321' );
		$exists = false;

		$existenceChecker = new TypeDispatchingExistenceChecker(
			[ 'property' => function () use ( $entityId, $exists ) {
				$propertyExistenceChecker = $this->createMock( EntityExistenceChecker::class );
				$propertyExistenceChecker->expects( $this->once() )
					->method( 'exists' )
					->with( $entityId )
					->willReturn( $exists );

				return $propertyExistenceChecker;
			} ],
			$this->newNeverCalledMockChecker()
		);

		$this->assertSame( $exists, $existenceChecker->exists( $entityId ) );
	}

	private function newNeverCalledMockChecker(): EntityExistenceChecker {
		$existenceChecker = $this->createMock( EntityExistenceChecker::class );
		$existenceChecker->expects( $this->never() )->method( $this->anything() );

		return $existenceChecker;
	}

	public function testExistsBatch() {
		$itemIds = [ new ItemId( 'Q123' ), new ItemId( 'Q456' ) ];
		$propertyIds = [ new PropertyId( 'P123' ), new PropertyId( 'P456' ) ];

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

		$existenceChecker = new TypeDispatchingExistenceChecker(
			[ 'item' => function () use ( $itemChecker ) {
				return $itemChecker;
			} ],
			$propertyChecker
		);

		$result = $existenceChecker->existsBatch( array_merge( $itemIds, $propertyIds ) );

		$expected = [
			'Q123' => true,
			'Q456' => false,
			'P123' => true,
			'P456' => false,
		];
		$this->assertSame( $expected, $result );
	}

}

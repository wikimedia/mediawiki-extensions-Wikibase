<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
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

}

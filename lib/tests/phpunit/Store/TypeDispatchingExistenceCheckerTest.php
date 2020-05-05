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
		$isDeleted = false;

		$defaultChecker = $this->createMock( EntityExistenceChecker::class );
		$defaultChecker->expects( $this->once() )
			->method( 'isDeleted' )
			->with( $entityId )
			->willReturn( $isDeleted );

		$existenceChecker = new TypeDispatchingExistenceChecker(
			[ 'item' => function () {
				return $this->newNeverCalledMockChecker();
			} ],
			$defaultChecker
		);

		$this->assertSame( $isDeleted, $existenceChecker->isDeleted( $entityId ) );
	}

	public function testGivenExistenceCheckerDefinedForEntityType_usesRespectiveExistenceChecker() {
		$entityId = new PropertyId( 'P321' );
		$isDeleted = true;

		$existenceChecker = new TypeDispatchingExistenceChecker(
			[ 'property' => function () use ( $entityId, $isDeleted ) {
				$propertyExistenceChecker = $this->createMock( EntityExistenceChecker::class );
				$propertyExistenceChecker->expects( $this->once() )
					->method( 'isDeleted' )
					->with( $entityId )
					->willReturn( $isDeleted );

				return $propertyExistenceChecker;
			} ],
			$this->newNeverCalledMockChecker()
		);

		$this->assertSame( $isDeleted, $existenceChecker->isDeleted( $entityId ) );
	}

	private function newNeverCalledMockChecker(): EntityExistenceChecker {
		$existenceChecker = $this->createMock( EntityExistenceChecker::class );
		$existenceChecker->expects( $this->never() )->method( $this->anything() );

		return $existenceChecker;
	}

}

<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\TypeDispatchingRedirectChecker;

/**
 * @covers \Wikibase\Lib\Store\TypeDispatchingRedirectChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TypeDispatchingRedirectCheckerTest extends TestCase {

	public function testGivenNoRedirectCheckerDefinedForEntityType_usesDefaultChecker() {
		$entityId = new PropertyId( 'P123' );
		$isRedirect = false;

		$defaultChecker = $this->createMock( EntityRedirectChecker::class );
		$defaultChecker->expects( $this->once() )
			->method( 'isRedirect' )
			->with( $entityId )
			->willReturn( $isRedirect );

		$redirectChecker = new TypeDispatchingRedirectChecker(
			[ 'item' => function () {
				return $this->newNeverCalledMockChecker();
			} ],
			$defaultChecker
		);

		$this->assertSame( $isRedirect, $redirectChecker->isRedirect( $entityId ) );
	}

	public function testGivenRedirectCheckerDefinedForEntityType_usesRespectiveRedirectChecker() {
		$entityId = new PropertyId( 'P321' );
		$isRedirect = true;

		$redirectChecker = new TypeDispatchingRedirectChecker(
			[ 'property' => function () use ( $entityId, $isRedirect ) {
				$propertyRedirectChecker = $this->createMock( EntityRedirectChecker::class );
				$propertyRedirectChecker->expects( $this->once() )
					->method( 'isRedirect' )
					->with( $entityId )
					->willReturn( $isRedirect );

				return $propertyRedirectChecker;
			} ],
			$this->newNeverCalledMockChecker()
		);

		$this->assertSame( $isRedirect, $redirectChecker->isRedirect( $entityId ) );
	}

	private function newNeverCalledMockChecker(): EntityRedirectChecker {
		$redirectChecker = $this->createMock( EntityRedirectChecker::class );
		$redirectChecker->expects( $this->never() )->method( $this->anything() );

		return $redirectChecker;
	}

}

<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityArticleIdLookup;
use Wikibase\Lib\Store\TypeDispatchingArticleIdLookup;

/**
 * @covers \Wikibase\Lib\Store\TypeDispatchingArticleIdLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TypeDispatchingArticleIdLookupTest extends TestCase {

	public function testGivenNoLookupDefinedForEntityType_usesDefaultLookup() {
		$entityId = new PropertyId( 'P123' );
		$articleId = 42;

		$defaultLookup = $this->createMock( EntityArticleIdLookup::class );
		$defaultLookup->expects( $this->once() )
			->method( 'getArticleId' )
			->with( $entityId )
			->willReturn( $articleId );

		$lookup = new TypeDispatchingArticleIdLookup(
			[ 'item' => function () {
				return $this->newNeverCalledMockLookup();
			} ],
			$defaultLookup
		);

		$this->assertSame( $articleId, $lookup->getArticleId( $entityId ) );
	}

	public function testGivenLookupDefinedForEntityType_usesRespectiveLookup() {
		$entityId = new PropertyId( 'P123' );
		$articleId = 23;

		$lookup = new TypeDispatchingArticleIdLookup(
			[ 'property' => function () use ( $entityId, $articleId ) {
				$propertyArticleIdLookup = $this->createMock( EntityArticleIdLookup::class );
				$propertyArticleIdLookup->expects( $this->once() )
					->method( 'getArticleId' )
					->with( $entityId )
					->willReturn( $articleId );

				return $propertyArticleIdLookup;
			} ],
			$this->newNeverCalledMockLookup()
		);

		$this->assertSame( $articleId, $lookup->getArticleId( $entityId ) );
	}

	private function newNeverCalledMockLookup(): EntityArticleIdLookup {
		$lookup = $this->createMock( EntityArticleIdLookup::class );
		$lookup->expects( $this->never() )->method( $this->anything() );

		return $lookup;
	}

}

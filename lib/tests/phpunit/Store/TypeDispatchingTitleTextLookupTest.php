<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\TypeDispatchingTitleTextLookup;

/**
 * @covers \Wikibase\Lib\Store\TypeDispatchingTitleTextLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TypeDispatchingTitleTextLookupTest extends TestCase {

	public function testGivenNoLookupDefinedForEntityType_usesDefaultLookup() {
		$entityId = new PropertyId( 'P123' );
		$titleText = 'Property:P123';

		$defaultLookup = $this->createMock( EntityTitleTextLookup::class );
		$defaultLookup->expects( $this->once() )
			->method( 'getPrefixedText' )
			->with( $entityId )
			->willReturn( $titleText );

		$lookup = new TypeDispatchingTitleTextLookup(
			[ 'item' => function () {
				return $this->newNeverCalledMockLookup();
			} ],
			$defaultLookup
		);

		$this->assertSame( $titleText, $lookup->getPrefixedText( $entityId ) );
	}

	public function testGivenLookupDefinedForEntityType_usesRespectiveLookup() {
		$entityId = new PropertyId( 'P321' );
		$titleText = 'Property:P321';

		$lookup = new TypeDispatchingTitleTextLookup(
			[ 'property' => function () use ( $entityId, $titleText ) {
				$propertyTitleTextLookup = $this->createMock( EntityTitleTextLookup::class );
				$propertyTitleTextLookup->expects( $this->once() )
					->method( 'getPrefixedText' )
					->with( $entityId )
					->willReturn( $titleText );

				return $propertyTitleTextLookup;
			} ],
			$this->newNeverCalledMockLookup()
		);

		$this->assertSame( $titleText, $lookup->getPrefixedText( $entityId ) );
	}

	private function newNeverCalledMockLookup(): EntityTitleTextLookup {
		$lookup = $this->createMock( EntityTitleTextLookup::class );
		$lookup->expects( $this->never() )->method( $this->anything() );

		return $lookup;
	}

}

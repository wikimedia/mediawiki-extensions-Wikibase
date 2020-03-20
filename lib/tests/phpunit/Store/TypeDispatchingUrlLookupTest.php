<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\TypeDispatchingUrlLookup;

/**
 * @covers \Wikibase\Lib\Store\TypeDispatchingUrlLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TypeDispatchingUrlLookupTest extends TestCase {

	public function testGivenNoLookupDefinedForEntityType_usesDefaultLookup() {
		$entityId = new PropertyId( 'P123' );
		$url = 'http://some-wikibase/wiki/Property:P123';

		$defaultLookup = $this->createMock( EntityUrlLookup::class );
		$defaultLookup->expects( $this->once() )
			->method( 'getFullUrl' )
			->with( $entityId )
			->willReturn( $url );

		$lookup = new TypeDispatchingUrlLookup(
			[ 'item' => function () {
				return $this->newNeverCalledMockLookup();
			} ],
			$defaultLookup
		);

		$this->assertSame( $url, $lookup->getFullUrl( $entityId ) );
	}

	public function testGivenLookupDefinedForEntityType_usesRespectiveLookup() {
		$entityId = new PropertyId( 'P321' );
		$url = 'http://some-wikibase/wiki/Property:P321';

		$lookup = new TypeDispatchingUrlLookup(
			[ 'property' => function () use ( $entityId, $url ) {
				$propertyUrlLookup = $this->createMock( EntityUrlLookup::class );
				$propertyUrlLookup->expects( $this->once() )
					->method( 'getFullUrl' )
					->with( $entityId )
					->willReturn( $url );

				return $propertyUrlLookup;
			} ],
			$this->newNeverCalledMockLookup()
		);

		$this->assertSame( $url, $lookup->getFullUrl( $entityId ) );
	}

	private function newNeverCalledMockLookup(): EntityUrlLookup {
		$lookup = $this->createMock( EntityUrlLookup::class );
		$lookup->expects( $this->never() )->method( $this->anything() );

		return $lookup;
	}

}

<?php

declare( strict_types = 1 );
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

	public function provideGetUrlMethods(): array {
		return [
			[ 'getFullUrl' ],
			[ 'getLinkUrl' ],
		];
	}

	/**
	 * @dataProvider provideGetUrlMethods
	 */
	public function testGivenNoLookupDefinedForEntityType_usesDefaultLookup( string $method ) {
		$entityId = new PropertyId( 'P123' );
		$url = 'http://some-wikibase/wiki/Property:P123';

		$defaultLookup = $this->createMock( EntityUrlLookup::class );
		$defaultLookup->expects( $this->once() )
			->method( $method )
			->with( $entityId )
			->willReturn( $url );

		$lookup = new TypeDispatchingUrlLookup(
			[ 'item' => function () {
				return $this->newNeverCalledMockLookup();
			} ],
			$defaultLookup
		);

		$this->assertSame( $url, $lookup->$method( $entityId ) );
	}

	/**
	 * @dataProvider provideGetUrlMethods
	 */
	public function testGivenLookupDefinedForEntityType_usesRespectiveLookup( string $method ) {
		$entityId = new PropertyId( 'P321' );
		$url = 'http://some-wikibase/wiki/Property:P321';

		$lookup = new TypeDispatchingUrlLookup(
			[ 'property' => function () use ( $entityId, $url, $method ) {
				$propertyUrlLookup = $this->createMock( EntityUrlLookup::class );
				$propertyUrlLookup->expects( $this->once() )
					->method( $method )
					->with( $entityId )
					->willReturn( $url );

				return $propertyUrlLookup;
			} ],
			$this->newNeverCalledMockLookup()
		);

		$this->assertSame( $url, $lookup->$method( $entityId ) );
	}

	private function newNeverCalledMockLookup(): EntityUrlLookup {
		$lookup = $this->createMock( EntityUrlLookup::class );
		$lookup->expects( $this->never() )->method( $this->anything() );

		return $lookup;
	}

}

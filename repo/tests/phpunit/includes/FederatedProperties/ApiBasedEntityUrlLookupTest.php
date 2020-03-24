<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\FederatedProperties\ApiBasedEntityTitleTextLookup;
use Wikibase\Repo\FederatedProperties\ApiBasedEntityUrlLookup;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiBasedEntityUrlLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiBasedEntityUrlLookupTest extends TestCase {

	public function provideTestGetFullUrl() {
		return [
			[ 'Q123', new ItemId( 'Q123' ), 'pretend.url/w/index.php?title=Q123' ],
			[ 'Item:Q456', new ItemId( 'Q456' ), 'pretend.url/w/index.php?title=Item%3AQ456' ],
			[ null, new PropertyId( 'P666' ), null ],
		];
	}

	/**
	 * @dataProvider provideTestGetFullUrl
	 */
	public function testGetFullUrl( $prefixedText, $entityId, $expected ) {
		$lookup = new ApiBasedEntityUrlLookup(
			$this->getApiBasedEntityTitleTextLookup( $prefixedText ),
			'pretend.url/w/'
		);
		$this->assertSame( $expected, $lookup->getFullUrl( $entityId ) );
	}

	private function getApiBasedEntityTitleTextLookup( $prefixedText ) {
		$mock = $this->createMock( ApiBasedEntityTitleTextLookup::class );
		$mock->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( $prefixedText );
		return $mock;
	}
}

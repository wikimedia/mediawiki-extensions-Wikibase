<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\FederatedProperties\ApiEntityTitleTextLookup;
use Wikibase\Repo\FederatedProperties\ApiEntityUrlLookup;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiEntityUrlLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiEntityUrlLookupTest extends TestCase {

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
		$lookup = new ApiEntityUrlLookup(
			$this->getApiEntityTitleTextLookup( $prefixedText ),
			'pretend.url/w/'
		);
		$this->assertSame( $expected, $lookup->getFullUrl( $entityId ) );
	}

	private function getApiEntityTitleTextLookup( $prefixedText ) {
		$mock = $this->createMock( ApiEntityTitleTextLookup::class );
		$mock->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( $prefixedText );
		return $mock;
	}
}

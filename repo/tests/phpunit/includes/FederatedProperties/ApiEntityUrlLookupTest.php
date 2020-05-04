<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
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

	public function provideTestGetUrl() {
		yield [ 'Q123', new ItemId( 'Q123' ), 'https://pretend.url/w/index.php?title=Q123' ];
		yield [ 'Item:Q456', new ItemId( 'Q456' ), 'https://pretend.url/w/index.php?title=Item%3AQ456' ];
		yield [ null, new PropertyId( 'P666' ), null ];
	}

	/**
	 * @dataProvider provideTestGetUrl
	 */
	public function testGetFullUrl( ?string $prefixedText, EntityId $entityId, ?string $expected ) {
		$lookup = new ApiEntityUrlLookup(
			$this->getApiEntityTitleTextLookup( $prefixedText ),
			'https://pretend.url/w/'
		);
		$this->assertSame( $expected, $lookup->getFullUrl( $entityId ) );
	}

	/**
	 * @dataProvider provideTestGetUrl
	 */
	public function testGetLinkUrl( ?string $prefixedText, EntityId $entityId, ?string $expected ) {
		$lookup = new ApiEntityUrlLookup(
			$this->getApiEntityTitleTextLookup( $prefixedText ),
			'https://pretend.url/w/'
		);
		$this->assertSame( $expected, $lookup->getLinkUrl( $entityId ) );
	}

	private function getApiEntityTitleTextLookup( ?string $prefixedText ) {
		$mock = $this->createMock( ApiEntityTitleTextLookup::class );
		$mock->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( $prefixedText );
		return $mock;
	}
}

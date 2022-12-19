<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
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
		yield [
			'P123',
			new FederatedPropertyId( 'https://pretend.url/entity/P123', 'P123' ),
			'https://pretend.url/w/index.php?title=P123',
		];
		yield [ null, new FederatedPropertyId( 'https://pretend.url/entity/P666', 'P666' ), null ];
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

	public function testGivenNotAFederatedPropertyId_getFullUrlThrows(): void {
		$lookup = new ApiEntityUrlLookup(
			$this->createStub( ApiEntityTitleTextLookup::class ),
			'https://pretend.url/w/'
		);

		$this->expectException( InvalidArgumentException::class );

		$lookup->getFullUrl( new NumericPropertyId( 'P666' ) );
	}

	public function testGivenNotAFederatedPropertyId_getLinkUrlThrows(): void {
		$lookup = new ApiEntityUrlLookup(
			$this->createStub( ApiEntityTitleTextLookup::class ),
			'https://pretend.url/w/'
		);

		$this->expectException( InvalidArgumentException::class );

		$lookup->getLinkUrl( new NumericPropertyId( 'P666' ) );
	}

	private function getApiEntityTitleTextLookup( ?string $prefixedText ) {
		$mock = $this->createMock( ApiEntityTitleTextLookup::class );
		$mock->method( 'getPrefixedText' )
			->willReturn( $prefixedText );
		return $mock;
	}
}

<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\FederatedProperties\ApiEntityNamespaceInfoLookup;
use Wikibase\Repo\FederatedProperties\ApiEntityTitleTextLookup;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiEntityTitleTextLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiEntityTitleTextLookupTest extends TestCase {

	public function provideTestGetFullUrl() {
		return [
			[ '', new ItemId( 'Q123' ), 'Q123' ],
			[ 'Item', new ItemId( 'Q456' ), 'Item:Q456' ],
			[ 'Property', new PropertyId( 'P789' ), 'Property:P789' ],
			[ null, new PropertyId( 'P666' ), null ],
		];
	}

	/**
	 * @dataProvider provideTestGetFullUrl
	 */
	public function testGetFullUrl( $namespaceName, $entityId, $expected ) {
		$lookup = new ApiEntityTitleTextLookup(
			$this->getApiEntityNamespaceInfoLookup( $namespaceName )
		);
		$this->assertSame( $expected, $lookup->getPrefixedText( $entityId ) );
	}

	private function getApiEntityNamespaceInfoLookup( $namespaceName ) {
		$mock = $this->createMock( ApiEntityNamespaceInfoLookup::class );
		$mock->expects( $this->any() )
			->method( 'getNamespaceNameForEntityType' )
			->willReturn( $namespaceName );
		return $mock;
	}
}

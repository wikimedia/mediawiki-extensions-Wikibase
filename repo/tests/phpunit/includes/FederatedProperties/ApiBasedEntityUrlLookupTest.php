<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\FederatedProperties\ApiBasedEntityNamespaceInfoLookup;
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
			[ '', new ItemId( 'Q123' ), 'pretend.url/w/index.php?title=Q123' ],
			[ 'Item', new ItemId( 'Q456' ), 'pretend.url/w/index.php?title=Item%3AQ456' ],
			[ 'Property', new PropertyId( 'P789' ), 'pretend.url/w/index.php?title=Property%3AP789' ],
			[ null, new PropertyId( 'P666' ), null ],
		];
	}

	/**
	 * @dataProvider provideTestGetFullUrl
	 */
	public function testGetFullUrl( $namespaceName, $entityId, $expected ) {
		$lookup = new ApiBasedEntityUrlLookup(
			$this->getApiBasedEntityNamespaceInfoLookup( $namespaceName ),
			'pretend.url/w/'
		);
		$this->assertSame( $expected, $lookup->getFullUrl( $entityId ) );
	}

	private function getApiBasedEntityNamespaceInfoLookup( $namespaceName ) {
		$mock = $this->createMock( ApiBasedEntityNamespaceInfoLookup::class );
		$mock->expects( $this->any() )
			->method( 'getNamespaceNameForEntityType' )
			->willReturn( $namespaceName );
		return $mock;
	}
}

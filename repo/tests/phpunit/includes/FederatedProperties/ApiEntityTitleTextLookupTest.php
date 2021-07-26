<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
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
			[
				'Property',
				new FederatedPropertyId( 'http://wikidata.org/entity/P789', 'P789' ),
				'Property:http://wikidata.org/entity/P789' // This is obvs wrong but reflects the current behavior. Fixed in a follow-up.
			],
			[ null, new FederatedPropertyId( 'http://wikidata.org/entity/P666', 'P666' ), null ],
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

	public function testGivenNotAFederatedPropertyId_getPrefixedTextThrows() {
		$lookup = new ApiEntityTitleTextLookup(
			$this->getApiEntityNamespaceInfoLookup( 'Property' )
		);

		$this->expectException( InvalidArgumentException::class );

		$lookup->getPrefixedText( new PropertyId( 'P666' ) );
	}

	private function getApiEntityNamespaceInfoLookup( $namespaceName ) {
		$mock = $this->createMock( ApiEntityNamespaceInfoLookup::class );
		$mock->method( 'getNamespaceNameForEntityType' )
			->willReturn( $namespaceName );
		return $mock;
	}

}

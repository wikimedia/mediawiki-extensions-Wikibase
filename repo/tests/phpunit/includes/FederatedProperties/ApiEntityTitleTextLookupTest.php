<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
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

	public function provideTestGetPrefixedText() {
		return [
			[ 'Property', new FederatedPropertyId( 'http://wikidata.org/entity/P789', 'P789' ), 'Property:P789' ],
			[ null, new FederatedPropertyId( 'http://wikidata.org/entity/P666', 'P666' ), null ],
		];
	}

	/**
	 * @dataProvider provideTestGetPrefixedText
	 */
	public function testGetPrefixedText( $namespaceName, $entityId, $expected ) {
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

		$lookup->getPrefixedText( new NumericPropertyId( 'P666' ) );
	}

	private function getApiEntityNamespaceInfoLookup( $namespaceName ) {
		$mock = $this->createMock( ApiEntityNamespaceInfoLookup::class );
		$mock->method( 'getNamespaceNameForEntityType' )
			->willReturn( $namespaceName );
		return $mock;
	}

}

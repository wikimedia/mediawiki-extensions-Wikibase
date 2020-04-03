<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\FederatedProperties\ApiPropertyDataTypeLookup;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiPropertyDataTypeLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiPropertyDataTypeLookupTest extends TestCase {

	public function testGetNamespaceNameForEntityType() {
		$propertyId = new PropertyId( 'P666' );
		$apiResultFile = __DIR__ . '/../../data/federatedProperties/wbgetentities-property-datatype.json';
		$expected = 'secretEvilDataType';

		$lookup = new ApiPropertyDataTypeLookup(
			$this->getApiClient( $apiResultFile )
		);

		$this->assertSame( $expected, $lookup->getDataTypeIdForProperty( $propertyId ) );
	}

	private function getApiClient( $responseDataFile ) {
		$client = $this->createMock( GenericActionApiClient::class );
		$client->expects( $this->any() )
			->method( 'get' )
			->willReturn( new Response( 200, [], file_get_contents( $responseDataFile ) ) );
		return $client;
	}

}

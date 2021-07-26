<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Repo\FederatedProperties\ApiEntityLookup;
use Wikibase\Repo\FederatedProperties\ApiPropertyDataTypeLookup;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;
use Wikibase\Repo\Tests\HttpResponseMockerTrait;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiPropertyDataTypeLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiPropertyDataTypeLookupTest extends TestCase {

	use HttpResponseMockerTrait;

	public function testGetDataTypeIdForProperty() {
		$propertyId = new FederatedPropertyId( 'http://wikidata.org/entity/P666', 'P666' );
		$apiResultFile = __DIR__ . '/../../data/federatedProperties/wbgetentities-property-datatype.json';
		$expected = 'secretEvilDataType';
		$apiEntityLookup = new ApiEntityLookup( $this->getApiClient( $apiResultFile ) );

		$apiEntityLookup->fetchEntities( [ $propertyId ] );

		$lookup = new ApiPropertyDataTypeLookup( $apiEntityLookup );

		$this->assertSame( $expected, $lookup->getDataTypeIdForProperty( $propertyId ) );
	}

	public function testGivenPropertyDoesNotExist_throwsException() {
		$p1 = new FederatedPropertyId( 'http://wikidata.org/entity/P1', 'P1' );
		$apiEntityLookup = new ApiEntityLookup(
			$this->getApiClient( __DIR__ . '/../../data/federatedProperties/wbgetentities-p1-missing.json' )
		);
		$lookup = new ApiPropertyDataTypeLookup(
			$apiEntityLookup
		);

		$apiEntityLookup->fetchEntities( [ $p1 ] );

		$this->expectException( PropertyDataTypeLookupException::class );

		$lookup->getDataTypeIdForProperty( $p1 );
	}

	private function getApiClient( $responseDataFile ) {
		$client = $this->createMock( GenericActionApiClient::class );
		$client->method( 'get' )
			->willReturn( $this->newMockResponse( file_get_contents( $responseDataFile ), 200 ) );
		return $client;
	}

}

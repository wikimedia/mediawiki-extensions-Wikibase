<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\Tests\NewEntitySource;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\SubEntityTypesMapper;
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
		$propertyId = new FederatedPropertyId( 'http://wikidata.org/entity/P666' );
		$apiResultFile = __DIR__ . '/../../data/federatedProperties/wbgetentities-property-datatype.json';
		$expected = 'secretEvilDataType';
		$apiEntityLookup = new ApiEntityLookup( $this->getApiClient( $apiResultFile ), $this->newMockEntitySourceLookup() );

		$apiEntityLookup->fetchEntities( [ $propertyId ] );

		$lookup = new ApiPropertyDataTypeLookup( $apiEntityLookup );

		$this->assertSame( $expected, $lookup->getDataTypeIdForProperty( $propertyId ) );
	}

	public function testGivenPropertyDoesNotExist_throwsException() {
		$p1 = new FederatedPropertyId( 'http://wikidata.org/entity/P1' );
		$apiEntityLookup = new ApiEntityLookup(
			$this->getApiClient( __DIR__ . '/../../data/federatedProperties/wbgetentities-p1-missing.json' ),
			$this->newMockEntitySourceLookup()
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

	private function newMockEntitySourceLookup(): EntitySourceLookup {
		$source = NewEntitySource::havingName( 'some source' )
			->withConceptBaseUri( 'http://wikidata.org/entity/' )
			->withType( EntitySource::TYPE_API )
			->build();
		$subEntityTypesMapper = new SubEntityTypesMapper( [] );
		$entitySourceDefinition = new EntitySourceDefinitions( [ $source ], $subEntityTypesMapper );
		return new EntitySourceLookup( $entitySourceDefinition, $subEntityTypesMapper );
	}

}

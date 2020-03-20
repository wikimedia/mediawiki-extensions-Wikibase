<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\FederatedProperties\ApiBasedEntityNamespaceInfoLookup;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiBasedEntityNamespaceInfoLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiBasedEntityNamespaceInfoLookupTest extends TestCase {

	private $dataFiles = [
		'query-siteinfo-namespaces-wikidata.json',
		'query-siteinfo-namespaces-wikibase.json',
	];

	private $data = [];

	protected function setUp() : void {
		parent::setUp();

		// Load data files once at the start of tests rather than for each test case.
		foreach ( $this->dataFiles as $file ) {
			$this->data[$file] = file_get_contents( __DIR__ . '/../../data/federatedProperties/' . $file );
		}
	}

	public function provideTestGetNamespaceNameForEntityType() {
		return [
			[ 'query-siteinfo-namespaces-wikidata.json', Item::ENTITY_TYPE, '' ],
			[ 'query-siteinfo-namespaces-wikidata.json', Property::ENTITY_TYPE, 'Property' ],
			[ 'query-siteinfo-namespaces-wikidata.json', 'foo', null ],
			[ 'query-siteinfo-namespaces-wikibase.json', Item::ENTITY_TYPE, 'Item' ],
			[ 'query-siteinfo-namespaces-wikibase.json', Property::ENTITY_TYPE, 'Property' ],
			[ 'query-siteinfo-namespaces-wikibase.json', 'notDefinedLocally', null ],
			[ 'query-siteinfo-namespaces-wikibase.json', 'notDefinedOnRemote', null ],
		];
	}

	private function getContentModelMappings() {
		return array_merge(
			WikibaseRepo::getDefaultInstance()->getContentModelMappings(),
			[ 'notDefinedOnRemote' => 'notDefinedOnRemote' ]
		);
	}

	/**
	 * @dataProvider provideTestGetNamespaceNameForEntityType
	 */
	public function testGetNamespaceNameForEntityType( $dataFile, $entityType, $expected ) {
		$lookup = new ApiBasedEntityNamespaceInfoLookup(
			$this->getApiClient( $dataFile ),
			$this->getContentModelMappings()
		);

		$this->assertSame( $expected, $lookup->getNamespaceNameForEntityType( $entityType ) );
	}

	private function getApiClient( $responseDataFile ) {
		$client = $this->createMock( GenericActionApiClient::class );
		$client->expects( $this->any() )
			->method( 'get' )
			->willReturnCallback( function() use ( $responseDataFile ) {
				return new Response( 200, [], $this->data[$responseDataFile] );
			} );
		return $client;
	}

}

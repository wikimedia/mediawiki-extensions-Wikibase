<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Repo\FederatedProperties\ApiEntityLookup;
use Wikibase\Repo\FederatedProperties\ApiPrefetchingTermLookup;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;
use Wikibase\Repo\Tests\HttpResponseMockerTrait;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiPrefetchingTermLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiPrefetchingTermLookupTest extends TestCase {

	use HttpResponseMockerTrait;

	private $responseDataFiles = [
		'q42-en' => 'api-prefetching-term-lookup-test-data-q42-en.json',
		'p18-en' => 'api-prefetching-term-lookup-test-data-p18-en.json',
		'p18-de' => 'api-prefetching-term-lookup-test-data-p18-de.json',
		'p18-en-de' => 'api-prefetching-term-lookup-test-data-p18-en-de.json',
		'p31-en' => 'api-prefetching-term-lookup-test-data-p31-en.json',
		'p31-de' => 'api-prefetching-term-lookup-test-data-p31-de.json',
		'p31-en-de' => 'api-prefetching-term-lookup-test-data-p31-en-de.json',
		'p18-p31-en' => 'api-prefetching-term-lookup-test-data-p18-p31-en.json',
		'p18-p31-de' => 'api-prefetching-term-lookup-test-data-p18-p31-de.json',
		'p18-p31-en-de' => 'api-prefetching-term-lookup-test-data-p18-p31-en-de.json'
	];

	private $data = [];

	private $p18;
	private $p31;
	private $q42;

	protected function setUp(): void {
		$this->q42 = new ItemId( 'Q42' );
		$this->p18 = new PropertyId( 'P18' );
		$this->p31 = new PropertyId( 'P31' );

		parent::setUp();
		// Load data files once at the start of tests rather than for each test case
		foreach ( $this->responseDataFiles as $key => $file ) {
			$content = file_get_contents( __DIR__ . '/../../data/federatedProperties/' . $file );
			$this->data[$file] = json_decode( $content );
		}
	}

	/**
	 * @return array [ entityIdString, languages, responseDataFile, expectedLabels[] ]
	 */
	public function entityIdsWithLanguagesAndExpectedLabelsProvider() {
		return [
			'q42-en' => [
				'Q42',
				[ 'en' ],
				$this->responseDataFiles[ 'q42-en' ],
				[ 'en' => 'Douglas Adams' ]
			],
			'p18-en' => [
				'P18',
				[ 'en' ],
				$this->responseDataFiles[ 'p18-en' ],
				[ 'en' => 'image' ]
			],
			'p18-en-de' => [
				'P18',
				[ 'en', 'de' ],
				$this->responseDataFiles[ 'p18-en-de' ],
				[ 'en' => 'image', 'de' => 'Bild' ]
			],
			'p31-en' => [
				'P31',
				[ 'en' ],
				$this->responseDataFiles[ 'p31-en' ],
				[ 'en' => 'instance of' ]
			],
			'p31-en-de' => [
				'P31',
				[ 'en', 'de' ],
				$this->responseDataFiles[ 'p31-en-de' ],
				[ 'en' => 'instance of', 'de' => 'ist ein(e)' ]
			],
		];
	}

	/**
	 * @dataProvider entityIdsWithLanguagesAndExpectedLabelsProvider
	 */
	public function testGetLabels( $entityIdString, $languages, $responseFile, $expectedLabels ) {
		$apiLookup = new ApiPrefetchingTermLookup(
			new ApiEntityLookup( $this->newMockApi( [ $entityIdString ], $responseFile ) )
		);

		$entityId = ( $entityIdString[0] == 'P' ) ? new PropertyId( $entityIdString ) : new ItemId( $entityIdString );
		$labels = $apiLookup->getLabels( $entityId, $languages );
		$this->assertEquals( $expectedLabels, $labels );
	}

	/**
	 * @dataProvider descriptionsTestProvider
	 */
	public function testGetDescriptions( EntityId $entityId, $languages, $responseFile, $expectedDescriptions ) {
		$apiLookup = new ApiPrefetchingTermLookup(
			new ApiEntityLookup( $this->newMockApi( [ $entityId->getSerialization() ], $responseFile ) )
		);

		$this->assertEquals(
			$expectedDescriptions,
			$apiLookup->getDescriptions( $entityId, $languages )
		);
	}

	public function descriptionsTestProvider() {
		yield 'en description' => [
			new PropertyId( 'P18' ),
			[ 'en' ],
			$this->responseDataFiles[ 'p18-en-de' ],
			[ 'en' => 'image of relevant illustration of the subject' ]
		];
		yield 'en and de descriptions' => [
			new PropertyId( 'P18' ),
			[ 'en', 'de' ],
			$this->responseDataFiles[ 'p18-en-de' ],
			[ 'en' => 'image of relevant illustration of the subject', 'de' => 'Foto, Grafik etc. des Objekts' ]
		];
	}

	public function testGetPrefetchedAliases() {
		$apiLookup = new ApiPrefetchingTermLookup(
			new ApiEntityLookup( $this->createMock( GenericActionApiClient::class ) )
		);

		$this->expectException( \BadMethodCallException::class );
		$apiLookup->getPrefetchedAliases( new PropertyId( 'P1' ), 'someLanguage' );
	}

	public function testPrefetchTermsAndGetPrefetchedTerm() {
		$api = $this->newMockApi(
			[ $this->p18->getSerialization(), $this->p31->getSerialization() ],
			$this->responseDataFiles[ 'p18-p31-en' ]
		);

		$apiLookup = new ApiPrefetchingTermLookup( new ApiEntityLookup( $api ) );

		$apiLookup->prefetchTerms( [ $this->p18, $this->p31 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );

		// verify that both P18 and P31 are buffered
		$this->assertSame( 'image', $apiLookup->getLabel( $this->p18, 'en' ) );
		$this->assertSame( 'instance of', $apiLookup->getLabel( $this->p31, 'en' ) );
	}

	public function testConsecutivePrefetch() {
		$api = $this->createMock( GenericActionApiClient::class );
		// expect two API request
		$api->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ $this->getRequestParameters( [ $this->p18->getSerialization() ] ) ],
				[ $this->getRequestParameters( [ $this->p31->getSerialization() ] ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->newMockResponse( json_encode( $this->data[ $this->responseDataFiles[ 'p18-en' ] ] ), 200 ),
				$this->newMockResponse( json_encode( $this->data[ $this->responseDataFiles[ 'p31-en' ] ] ), 200 )
			);

		$apiLookup = new ApiPrefetchingTermLookup( new ApiEntityLookup( $api ) );
		$apiLookup->prefetchTerms( [ $this->p18 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );
		$this->assertSame( 'image', $apiLookup->getLabel( $this->p18, 'en' ) );

		$apiLookup->prefetchTerms( [ $this->p31 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );
		// verify that P18 is still buffered
		$this->assertSame( 'image', $apiLookup->getLabel( $this->p18, 'en' ) );
		// verify that P31 has been added to buffer
		$this->assertSame( 'instance of', $apiLookup->getLabel( $this->p31, 'en' ) );
	}

	public function testConsecutivePrefetch_alreadyInBuffer() {
		$api = $this->createMock( GenericActionApiClient::class );
		// expect two API request
		$api->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->withConsecutive(
				[ $this->getRequestParameters( [ $this->p18->getSerialization() ] ) ],
				// the second request will NOT ask for P18, that has already been fetched
				[ $this->getRequestParameters( [ $this->p31->getSerialization() ] ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->newMockResponse( json_encode( $this->data[ $this->responseDataFiles[ 'p18-en' ] ] ), 200 ),
				$this->newMockResponse( json_encode( $this->data[ $this->responseDataFiles[ 'p31-en' ] ] ), 200 )
			);

		$apiLookup = new ApiPrefetchingTermLookup( new ApiEntityLookup( $api ) );

		// prefetch P18 first and verify the label
		$apiLookup->prefetchTerms( [ $this->p18 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );
		$this->assertSame( 'image', $apiLookup->getLabel( $this->p18, 'en' ) );

		// ask to prefetch P18 and P31, but only P31 will be requested from API here
		$apiLookup->prefetchTerms( [ $this->p18, $this->p31 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );
		// verify that P18 is still buffered
		$this->assertSame( 'image', $apiLookup->getLabel( $this->p18, 'en' ) );
		// verify that P31 has been added to buffer
		$this->assertSame( 'instance of', $apiLookup->getLabel( $this->p31, 'en' ) );
	}

	public function testConsecutivePrefetch_newLanguage() {
		$api = $this->newMockApi(
			[ $this->p18->getSerialization() ],
			$this->responseDataFiles[ 'p18-en-de' ]
		);

		$apiLookup = new ApiPrefetchingTermLookup( new ApiEntityLookup( $api ) );

		// prefetch only language 'en' for P18 first
		$apiLookup->prefetchTerms( [ $this->p18 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );

		$this->assertSame( 'image', $apiLookup->getLabel( $this->p18, 'en' ) );

		// prefetch only language 'de' for P18 next
		$apiLookup->prefetchTerms( [ $this->p18 ], [ TermTypes::TYPE_LABEL ], [ 'de' ] );

		// verify that P18-en is still buffered
		$this->assertSame( 'image', $apiLookup->getLabel( $this->p18, 'en' ) );
		// verify that P18-de has been added to buffer

		$this->assertSame( 'Bild', $apiLookup->getLabel( $this->p18, 'de' ) );
	}

	public function testGetPrefetchedTerm_notPrefetched() {
		$apiLookup = new ApiPrefetchingTermLookup( new ApiEntityLookup( $this->createMock( GenericActionApiClient::class ) ) );
		$this->assertNull( $apiLookup->getPrefetchedTerm( $this->p18, TermTypes::TYPE_LABEL, 'en' ) );
	}

	public function testGetPrefetchedTerm_doesNotExist() {
		// en and de are requested, but only return en (pretend neither entity has a de label)
		$api = $this->newMockApi(
			[ $this->p18->getSerialization(), $this->p31->getSerialization() ],
			$this->responseDataFiles[ 'p18-p31-en' ]
		);

		$apiLookup = new ApiPrefetchingTermLookup( new ApiEntityLookup( $api ) );
		$apiLookup->prefetchTerms(
			[ $this->p18, $this->p31 ],
			[ TermTypes::TYPE_LABEL ],
			[ 'en', 'de' ]
		);

		$this->assertFalse( $apiLookup->getPrefetchedTerm( $this->p18, TermTypes::TYPE_LABEL, 'de' ) );
	}

	public function testPrefetchTerms_sameTermsTwice() {
		$api = $this->newMockApi(
			[ $this->p18->getSerialization() ],
			$this->responseDataFiles[ 'p18-en' ]
		);
		$apiLookup = new ApiPrefetchingTermLookup( new ApiEntityLookup( $api ) );

		$apiLookup->prefetchTerms( [ $this->p18 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );
		$apiLookup->prefetchTerms( [ $this->p18 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );
		$this->assertTrue( true ); // no error
	}

	private function getRequestParameters( $ids ) {
		$params = [
				'action' => 'wbgetentities',
				'ids' => implode( '|', $ids ),
				'props' => 'labels|descriptions|datatype',
				'format' => 'json'
			];

		return $params;
	}

	private function newMockApi( $ids, $responseDataFile ) {
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( $this->getRequestParameters( $ids ) )
			->willReturn( $this->newMockResponse( json_encode( $this->data[ $responseDataFile ] ), 200 ) );

		return $api;
	}

}

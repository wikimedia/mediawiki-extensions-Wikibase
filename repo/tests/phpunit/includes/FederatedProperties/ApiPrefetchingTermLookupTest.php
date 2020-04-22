<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Repo\FederatedProperties\ApiPrefetchingTermLookup;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;
use function GuzzleHttp\Psr7\stream_for;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiPrefetchingTermLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiPrefetchingTermLookupTest extends TestCase {

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

	private $p18, $p31;

	protected function setUp(): void {
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
			$this->newMockApi( [ $entityIdString ], $languages, $responseFile )
		);

		$entityId = ( $entityIdString[0] == 'P' ) ? new PropertyId( $entityIdString ) : new ItemId( $entityIdString );
		$labels = $apiLookup->getLabels( $entityId, $languages );
		$this->assertEquals( $expectedLabels, $labels );
	}

	public function testGetDescriptions() {
		$apiLookup = new ApiPrefetchingTermLookup(
			$this->createMock( GenericActionApiClient::class )
		);

		$this->expectException( \BadMethodCallException::class );
		$apiLookup->getDescriptions( new PropertyId( 'P1' ), [ 'some', 'languages' ] );
	}

	public function testGetPrefetchedAliases() {
		$apiLookup = new ApiPrefetchingTermLookup(
			$this->createMock( GenericActionApiClient::class )
		);

		$this->expectException( \BadMethodCallException::class );
		$apiLookup->getPrefetchedAliases( new PropertyId( 'P1' ), 'someLanguage' );
	}

	public function testPrefetchTermsAndGetPrefetchedTerm() {
		$api = $this->newMockApi(
			[ $this->p18->getSerialization(), $this->p31->getSerialization() ],
			[ 'en' ],
			$this->responseDataFiles[ 'p18-p31-en' ]
		);

		$apiLookup = new ApiPrefetchingTermLookup( $api );

		$apiLookup->prefetchTerms( [ $this->p18, $this->p31 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );

		// verify that both P18 and P31 are buffered
		$this->assertSame( 'image', $apiLookup->getLabel( $this->p18, 'en' ) );
		$this->assertSame( 'instance of', $apiLookup->getLabel( $this->p31, 'en' ) );
	}

	public function testConsecutivePrefetch() {
		$api = $this->createMock( GenericActionApiClient::class );
		// expect two API request
		$api->expects( $this->any() )
			->method( 'get' )
			->withConsecutive(
				[ $this->getRequestParameters( [ $this->p18->getSerialization() ], [ 'en' ] ) ],
				[ $this->getRequestParameters( [ $this->p31->getSerialization() ], [ 'en' ] ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->newMockResponse( $this->responseDataFiles[ 'p18-en' ], 200 ),
				$this->newMockResponse( $this->responseDataFiles[ 'p31-en' ], 200 )
			);

		$apiLookup = new ApiPrefetchingTermLookup( $api );

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
		$api->expects( $this->any() )
			->method( 'get' )
			->withConsecutive(
				[ $this->getRequestParameters( [ $this->p18->getSerialization() ], [ 'en' ] ) ],
				// the second request will NOT ask for P18, that has already been fetched
				[ $this->getRequestParameters( [ $this->p31->getSerialization() ], [ 'en' ] ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->newMockResponse( $this->responseDataFiles[ 'p18-en' ], 200 ),
				$this->newMockResponse( $this->responseDataFiles[ 'p31-en' ], 200 )
			);

		$apiLookup = new ApiPrefetchingTermLookup( $api );

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
		$api = $this->createMock( GenericActionApiClient::class );
		// expect two API request
		$api->expects( $this->any() )
			->method( 'get' )
			->withConsecutive(
				[ $this->getRequestParameters( [ $this->p18->getSerialization() ], [ 'en' ] ) ],
				[ $this->getRequestParameters( [ $this->p18->getSerialization() ], [ 'de' ] ) ]
			)
			->willReturnOnConsecutiveCalls(
				$this->newMockResponse( $this->responseDataFiles[ 'p18-en' ], 200 ),
				$this->newMockResponse( $this->responseDataFiles[ 'p18-de' ], 200 )
			);

		$apiLookup = new ApiPrefetchingTermLookup( $api );

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
		$apiLookup = new ApiPrefetchingTermLookup( $this->createMock( GenericActionApiClient::class ) );
		$this->assertNull( $apiLookup->getPrefetchedTerm( $this->p18, TermTypes::TYPE_LABEL, 'en' ) );
	}

	public function testGetPrefetchedTerm_doesNotExist() {
		// en and de are requested, but only return en (pretend neither entity has a de label)
		$api = $this->newMockApi(
			[ $this->p18->getSerialization(), $this->p31->getSerialization() ],
			[ 'en', 'de' ],
			$this->responseDataFiles[ 'p18-p31-en' ]
		);

		$apiLookup = new ApiPrefetchingTermLookup( $api );
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
			[ 'en' ],
			$this->responseDataFiles[ 'p18-en' ]
		);
		$apiLookup = new ApiPrefetchingTermLookup( $api );

		$apiLookup->prefetchTerms( [ $this->p18 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );
		$apiLookup->prefetchTerms( [ $this->p18 ], [ TermTypes::TYPE_LABEL ], [ 'en' ] );
		$this->assertTrue( true ); // no error
	}

	private function getRequestParameters( $ids, $languageCodes ) {
		$params = [
				'action' => 'wbgetentities',
				'ids' => implode( '|', $ids ),
				'props' => 'labels',
		        'languages' => implode( '|', $languageCodes ),
			    'format' => 'json'
			];

		return $params;
	}

	private function newMockApi( $ids, $languageCodes, $responseDataFile ) {
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( $this->getRequestParameters( $ids, $languageCodes ) )
			->willReturn( $this->newMockResponse( $responseDataFile, 200 ) );

		return $api;
	}

	private function newMockResponse( $responseDataFile, $statusCode ) {
		$mwResponse = $this->createMock( ResponseInterface::class );
		$mwResponse->expects( $this->any() )
			->method( 'getStatusCode' )
			->willReturn( $statusCode );
		$mwResponse->expects( $this->any() )
			->method( 'getBody' )
			->willReturn( stream_for( json_encode( $this->data[ $responseDataFile ] ) ) );
		return $mwResponse;
	}
}

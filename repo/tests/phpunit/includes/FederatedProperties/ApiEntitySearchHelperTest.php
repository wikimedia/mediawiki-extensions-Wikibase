<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use Psr\Http\Message\ResponseInterface;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\PropertyDataTypeSearchHelper;
use Wikibase\Repo\FederatedProperties\ApiEntitySearchHelper;
use Wikibase\Repo\FederatedProperties\ApiRequestException;
use Wikibase\Repo\FederatedProperties\GenericActionApiClient;
use function GuzzleHttp\Psr7\stream_for;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiEntitySearchHelper
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiEntitySearchHelperTest extends TestCase {

	private $responseDataFiles = [
		'api-entity-search-helper-test-data-emptyResponse.json',
		'api-entity-search-helper-test-data-oneResponse.json',
		'api-entity-search-helper-test-data-twoResponse.json',
		'api-entity-search-helper-test-data-entityIdResponse.json',
		'api-entity-search-helper-test-data-multipleEntityIdResponse.json',
		'api-entity-search-helper-test-data-errorResponse.json',
		'api-entity-search-helper-test-data-unexpectedResponse.json'
	];

	private $data = [];

	protected function setUp() : void {

		parent::setUp();
		// Load data files once at the start of tests rather than for each test case
		foreach ( $this->responseDataFiles as $file ) {
			$content = file_get_contents( __DIR__ . '/../../data/federatedProperties/' . $file );
			$this->data[$file] = json_decode( $content );
		}
	}

	/**
	 * @dataProvider paramsAndExpectedResponseProvider
	 * @param string $responseDataFile
	 * @param int $expectedResultCount
	 * @param array $expectedResultsEntityId
	 * @throws ApiRequestException
	 */
	public function testGetRankedSearchResults( $langCode, $params, $responseDataFile, $expectedResultsEntityIds ) {

		$params = array_merge( $params, [ 'language' => $langCode, 'uselang' => $langCode, 'format' => 'json' ] );

		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( $params )
			->willReturn( $this->newMockResponse( $responseDataFile, 200 ) );

		$apiEntitySearchHelper = new ApiEntitySearchHelper( $api );
		$responseData = $this->data[ $responseDataFile ];
		$results = $apiEntitySearchHelper->getRankedSearchResults(
			$params[ 'search' ],
			$langCode,
			'property',
			$params[ 'limit' ],
			$params[ 'strictlanguage' ]

		);

		$this->assertEquals( count( $expectedResultsEntityIds ), count( $results ) );
		$this->assertEquals( $expectedResultsEntityIds, array_keys( $results ) );

		foreach ( $expectedResultsEntityIds as $resultId ) {

			$expectedResult = $this->getResponseDataForId( $responseData->search, $resultId );
			$resultToTest = $results[ $resultId ];

			$this->assertTrue( $resultToTest instanceof TermSearchResult );

			if ( $expectedResult[ 'match' ][ 'type' ] === 'entityId' ) {

				$this->assertEquals(
					new Term(
						'qid', $expectedResult[ 'match' ][ 'text' ] ),
						$resultToTest->getMatchedTerm()
					);
			} else {

				$this->assertEquals(
					new Term(
						$expectedResult[ 'match' ][ 'language' ],
						$expectedResult[ 'match' ][ 'text' ]
					),
					$resultToTest->getMatchedTerm()
				);
			}
			$this->assertEquals(
				$expectedResult[ 'match' ][ 'type' ],
				$resultToTest->getMatchedTermType()
			);
			$this->assertEquals(
				new PropertyId( $expectedResult[ 'id' ] ),
				$resultToTest->getEntityId()
			);
			$this->assertEquals(
				new Term( $langCode, $expectedResult[ 'label' ] ),
				$resultToTest->getDisplayLabel()
			);
			$this->assertEquals(
				new Term( $langCode, $expectedResult[ 'description' ] ),
				$resultToTest->getDisplayDescription()
			);
			$this->assertEquals(
				$expectedResult['datatype'],
				$resultToTest->getMetaData()[PropertyDataTypeSearchHelper::DATATYPE_META_DATA_KEY]
			);
		}
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

	private function getResponseDataForId( $searchResponses, $resultId ) {
		//convert $searchResponse to array
		$searchResponses = \GuzzleHttp\json_decode( \GuzzleHttp\json_encode( $searchResponses ), true );
		foreach ( $searchResponses as $response ) {
			if ( $response['id'] === $resultId ) {
				return $response;

			}
		}

		return [];
	}

	/**
	 * @dataProvider invalidParamsAndUnexpectedResponseProvider
	 * @param string $responseDataFile
	 * @param int $expectedResultCount
	 * @param array $expectedResultsEntityId
	 */
	public function testApiResponseStructureIsValid( $langCode, $params, $responseDataFile, $statusCode ) {
		$params = array_merge( $params, [ 'language' => $langCode, 'uselang' => $langCode, 'format' => 'json' ] );
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( $params )
			->willReturn( $this->newMockResponse( $responseDataFile, $statusCode ) );
		$apiEntitySearchHelper = new ApiEntitySearchHelper( $api );

		try {
			$apiEntitySearchHelper->getRankedSearchResults(
				$params[ 'search' ],
				$langCode,
				'property',
				$params[ 'limit' ],
				$params[ 'strictlanguage' ]

			);
		} catch ( ApiRequestException $exception ) {
			$this->assertEquals( $exception->getMessage(), 'Unexpected response output' );
		}
	}

	/**
	 * @return array [ searchlang, searchParams[], responseDataFile, responseStatusCode ]
	 */
	public function invalidParamsAndUnexpectedResponseProvider() {
		return [
			'errorResponse' => [
				'xyz',
				[
					'action' => 'wbsearchentities',
					'search' => 'foo',
					'type' => 'property',
					'limit' => 10,
					'strictlanguage' => false
				],
				'api-entity-search-helper-test-data-errorResponse.json',
				400
			],
			'unexpectedResponse' => [
				'en',
				[
					'action' => 'wbsearchentities',
					'search' => 'foo',
					'type' => 'property',
					'limit' => 10,
					'strictlanguage' => false
				],
				'api-entity-search-helper-test-data-unexpectedResponse.json',
				null
			]
		];
	}

	/**
	 * @return array [ searchlang, searchParams[], responseDataFile, expectedResultEntityIds[] ]
	 */
	public function paramsAndExpectedResponseProvider() {
		return [
			'emptyResponse' => [
				'en',
				[
					'action' => 'wbsearchentities',
					'search' => 'foo',
					'type' => 'property',
					'limit' => 10,
					'strictlanguage' => false
				],
				'api-entity-search-helper-test-data-emptyResponse.json',
				[],
			],
			'twoResponse' => [
				'en',
				[
					'action' => 'wbsearchentities',
					'search' => 'publication date',
					'type' => 'property',
					'limit' => 10,
					'strictlanguage' => false
				],
				'api-entity-search-helper-test-data-twoResponse.json',
				[ 'P577', 'P14' ],
			],
			'oneReponse' => [
				'de',
				[
					'action' => 'wbsearchentities',
					'search' => 'Publikationsdatum',
					'type' => 'property',
					'limit' => 10,
					'strictlanguage' => false
				],
				'api-entity-search-helper-test-data-oneResponse.json',
				[ 'P14' ],
			],
			'entityIdResponse' => [
				'de',
				[
					'action' => 'wbsearchentities',
					'search' => 'P31',
					'type' => 'property',
					'limit' => 10,
					'strictlanguage' => false
				],
				'api-entity-search-helper-test-data-entityIdResponse.json',
				[ 'P31' ],
			],
			'mixedEntityIdResponse' => [
				'de',
				[
					'action' => 'wbsearchentities',
					'search' => 'P147',
					'type' => 'property',
					'limit' => 10,
					'strictlanguage' => false
				],
				'api-entity-search-helper-test-data-multipleEntityIdResponse.json',
				[ 'P147', 'P160020' ]
			]
		];
	}
}

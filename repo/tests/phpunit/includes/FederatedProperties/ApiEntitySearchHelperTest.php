<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use Psr\Http\Message\ResponseInterface;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Interactors\TermSearchResult;
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

	/**
	 * @dataProvider paramsAndExpectedResponseProvider
	 * @param string $responseData
	 * @param int $expectedResultCount
	 * @param array $expectedResultsEntityId
	 * @throws ApiRequestException
	 */
	public function testGetRankedSearchResults( $langCode, $params, $responseData, $expectedResultsEntityIds ) {

		$params = array_merge( $params, [ 'language' => $langCode, 'uselang' => $langCode, 'format' => 'json' ] );

		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( $params )
			->willReturn( $this->newMockResponse( $responseData, 200 ) );

		$apiEntitySearchHelper = new ApiEntitySearchHelper( $api );

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

			$expectedResult = $this->getResponseDataForId( $responseData[ 'search' ], $resultId );
			$resultToTest = $results[ $resultId ];

			$this->assertTrue( $resultToTest instanceof TermSearchResult );
			$this->assertEquals(
				new Term( $expectedResult[ 'match' ][ 'language' ], $expectedResult[ 'match' ][ 'text' ] ),
				$resultToTest->getMatchedTerm()
			);
			$this->assertEquals( $expectedResult[ 'match' ][ 'type' ], $resultToTest->getMatchedTermType() );
			$this->assertEquals( new PropertyId( $expectedResult[ 'id' ] ), $resultToTest->getEntityId() );
			$this->assertEquals( new Term( $langCode, $expectedResult[ 'label' ] ), $resultToTest->getDisplayLabel() );
			$this->assertEquals( new Term( $langCode, $expectedResult[ 'description' ] ), $resultToTest->getDisplayDescription() );
		}
	}

	private function newMockResponse( $responseData, $statusCode ) {

		$mwResponse = $this->createMock( ResponseInterface::class );
		$mwResponse->expects( $this->any() )
			->method( 'getStatusCode' )
			->willReturn( $statusCode );
		$mwResponse->expects( $this->any() )
			->method( 'getBody' )
			->willReturn( stream_for( json_encode( $responseData ) ) );
		return $mwResponse;
	}

	private function getResponseDataForId( $searchResponses, $resultId ) {

		foreach ( $searchResponses as $response ) {
			if ( $response[ 'id' ] === $resultId ) {
				return $response;
			}
		}

		return [];
	}

	/**
	 * @dataProvider invalidParamsAndUnexpectedResponseProvider
	 * @param string $responseData
	 * @param int $expectedResultCount
	 * @param array $expectedResultsEntityId
	 */
	public function testGetRankedSearchResultsThrowsExceptionForFailureApiResponses( $langCode, $params, $responseData, $statusCode ) {

		$params = array_merge( $params, [ 'language' => $langCode, 'uselang' => $langCode, 'format' => 'json' ] );
		$api = $this->createMock( GenericActionApiClient::class );
		$api->expects( $this->once() )
			->method( 'get' )
			->with( $params )
			->willReturn( $this->newMockResponse( $responseData, $statusCode ) );
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
	 * @return array [ searchlang, searchParams[], responseData[], responseStatusCode ]
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
				[
					'error' =>
						[
							'code' => 'badvalue',
							'info' => 'Unrecognized value for parameter "language": xyz.',
							'*' => 'See https://wikidata.beta.wmflabs.org/w/api.php for API usage. Subscribe to the mediawiki-api-announce
							mailing list at &lt;https://lists.wikimedia.org/mailman/listinfo/mediawiki-api-announce&gt;
							for notice of API deprecations and breaking changes.',
						],
					'servedby' => 'deployment-mediawiki-07',
				],
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
				[ 'response' => 'unexpectedStructure' ],
				null
			]
		];
	}

	/**
	 * @return array [ searchlang, searchParams[], responseData[], expectedResultEntityIds[] ]
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
				[
					'searchinfo' =>
						[ 'search' => 'nonExistingProperty' ],
					'search' => [],
					'success' => 1
				],
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
				[
					'searchinfo' =>
						[ 'search' => 'publication date' ],
					'search' =>
						[
							0 =>
								[
									'repository' => 'local',
									'id' => 'P766',
									'concepturi' => 'https://wikidata.beta.wmflabs.org/entity/P766',
									'title' => 'Property:P766',
									'pageid' => 16633,
									'url' => 'https://wikidata.beta.wmflabs.org/wiki/Property:P766',
									'datatype' => 'time',
									'label' => 'date of publication',
									'description' => 'date when this work was published',
									'match' =>
										[
											'type' => 'alias',
											'language' => 'en',
											'text' => 'publication date',
										],
									'aliases' =>
										[ 0 => 'publication date' ],
								],
							1 =>
								[
									'repository' => 'local',
									'id' => 'P14',
									'concepturi' => 'https://wikidata.beta.wmflabs.org/entity/P14',
									'title' => 'Property:P14',
									'pageid' => 6426,
									'url' => 'https://wikidata.beta.wmflabs.org/wiki/Property:P14',
									'datatype' => 'string',
									'label' => 'publication date',
									'description' => 'eVrebfchLUnnzaCGDytx test',
									'match' =>
										[
											'type' => 'label',
											'language' => 'en',
											'text' => 'publication date',
										],
								],
						],
					'success' => 1,
				],
				[ 'P766', 'P14' ],
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
				[
					'searchinfo' =>
						[ 'search' => 'Publikationsdatum' ],
					'search' =>
						[
							0 =>
								[
									'repository' => 'local',
									'id' => 'P14',
									'concepturi' => 'https://wikidata.beta.wmflabs.org/entity/P14',
									'title' => 'Property:P14',
									'pageid' => 6426,
									'url' => 'https://wikidata.beta.wmflabs.org/wiki/Property:P14',
									'datatype' => 'string',
									'label' => 'Publikationsdatum',
									'description' => 'Beschreibung deutsch',
									'match' =>
										[
											'type' => 'label',
											'language' => 'de',
											'text' => 'Publikationsdatum',
										],
								],
						],
					'success' => 1,
				],
				[ 'P14' ],
			]
		];
	}
}

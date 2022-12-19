<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\SearchEntities
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
class SearchEntitiesTest extends FederatedPropertiesApiTestCase {

	protected function setUp(): void {
		$this->withLocalPropertySource = true;

		parent::setUp();
	}

	public function testFederatedPropertiesFailure() {
		$this->setSourceWikiUnavailable();

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-search-api-error-message' ) );
		$this->doApiRequestWithToken( [
			'action' => 'wbsearchentities',
			'search' => 'Foo',
			'type' => 'property',
			'language' => 'sv',
			'strictlanguage' => true,
		] );
	}

	public function testSearchTermMatchingBothLocalAndFederatedProperty(): void {
		$localPropLabel = "i'm a local prop";
		$fedPropLabel = "i'm a feddy prop";
		$fedPropLabelLanguage = 'en-gb';
		$searchTerm = "i'm a";

		$localPropDataType = 'string';
		$localProperty = new Property( null, new Fingerprint( new TermList( [ new Term( 'en', $localPropLabel ) ] ) ), $localPropDataType );
		$this->getEntityStore()->saveEntity( $localProperty, 'test prop', $this->getTestUser()->getUser(), EDIT_NEW );
		$localPropertyId = $localProperty->getId()->getSerialization();

		$fedPropRemoteId = 'P123';
		$fedPropId = $this->newFederatedPropertyIdFromPId( $fedPropRemoteId );

		$searchRequest = [
			'action' => 'wbsearchentities',
			'search' => $searchTerm,
			'type' => 'property',
			'language' => 'en',
		];

		$fedPropTitleText = "Property:$fedPropRemoteId";
		$fedPropDataType = 'string';
		$fedPropConceptURI = "http://wikidata.beta.wmflabs.org/entity/$fedPropRemoteId";
		$this->mockSourceApiRequests( [
			$this->getSiteInfoNamespaceRequestData(),
			[
				$searchRequest,
				[
					'success' => 1,
					'search' => [
						[
							'id' => $fedPropRemoteId,
							'title' => $fedPropTitleText,
							'pageid' => 666,
							'url' => "https://wikidata.beta.wmflabs.org/wiki/Property:$fedPropRemoteId",
							'datatype' => $fedPropDataType,
							'display' => [
								'label' => [ 'value' => $fedPropLabel, 'language' => $fedPropLabelLanguage ],
							],
							'label' => $fedPropLabel,
							'concepturi' => $fedPropConceptURI,
							'match' => [
								'type' => 'label',
								'language' => 'en',
								'text' => $fedPropLabel,
							],
						],
					],
				],
			],
		] );

		[ $result ] = $this->doApiRequestWithToken( $searchRequest );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertCount( 2, $result['search'] );

		$this->assertSame( $localPropertyId, $result['search'][0]['id'] );
		$this->assertSame( $localPropLabel, $result['search'][0]['label'] );
		$this->assertSame( $localPropDataType, $result['search'][0]['datatype'] );

		$this->assertSame( $fedPropId->getSerialization(), $result['search'][1]['id'] );
		$this->assertSame( $fedPropLabel, $result['search'][1]['label'] );
		$this->assertSame( $fedPropLabel, $result['search'][1]['display']['label']['value'] );
		$this->assertSame( $fedPropLabelLanguage, $result['search'][1]['display']['label']['language'] );
		$this->assertSame( $fedPropConceptURI, $result['search'][1]['concepturi'] );
		$this->assertSame( $fedPropDataType, $result['search'][1]['datatype'] );
		$this->assertSame( $fedPropTitleText, $result['search'][1]['title'] );
		$this->assertSame(
			WikibaseRepo::getSettings()->getSetting( 'federatedPropertiesSourceScriptUrl' )
			. 'index.php?title=' . urlencode( $fedPropTitleText ),
			$result['search'][1]['url']
		);
	}

	public function testSearchByIdMatchingBothLocalAndFederatedProperty(): void {
		$localPropLabel = 'local prop';
		$localProperty = new Property( null, new Fingerprint( new TermList( [ new Term( 'en', $localPropLabel ) ] ) ), 'string' );
		$this->getEntityStore()->saveEntity( $localProperty, 'test prop', $this->getTestUser()->getUser(), EDIT_NEW );
		$localPropertyId = $localProperty->getId()->getSerialization();

		$searchRequest = [
			'action' => 'wbsearchentities',
			'search' => $localPropertyId,
			'type' => 'property',
			'language' => 'en',
		];

		$fedPropId = $this->newFederatedPropertyIdFromPId( $localPropertyId );
		$fedPropRemoteId = $fedPropId->getRemoteIdSerialization();

		$this->mockSourceApiRequests( [
			$this->getSiteInfoNamespaceRequestData(),
			[
				$searchRequest,
				[
					'success' => 1,
					'search' => [
						[
							'id' => $fedPropRemoteId,
							'title' => "Property:$fedPropRemoteId",
							'pageid' => 666,
							'url' => "https://wikidata.beta.wmflabs.org/wiki/Property:$fedPropRemoteId",
							'datatype' => 'string',
							'label' => 'fed prop',
							'concepturi' => "http://wikidata.beta.wmflabs.org/entity/$fedPropRemoteId",
							'match' => [
								'type' => 'entityId',
								'text' => $fedPropId->getSerialization(),
							],
						],
					],
				],
			],
		] );

		[ $result ] = $this->doApiRequestWithToken( $searchRequest );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertCount( 2, $result['search'] );
		$this->assertSame( $localPropertyId, $result['search'][0]['id'] );
		$this->assertSame( $fedPropId->getSerialization(), $result['search'][1]['id'] );
	}

	public function testSearchMatchingFederatedPropertyWithoutDisplaySupport(): void {
		$fedPropLabel = "this is a feddy prop";
		$searchTerm = "this is a";
		$searchLanguage = 'en';

		$fedPropRemoteId = 'P123';
		$fedPropId = $this->newFederatedPropertyIdFromPId( $fedPropRemoteId );

		$searchRequest = [
			'action' => 'wbsearchentities',
			'search' => $searchTerm,
			'type' => 'property',
			'language' => $searchLanguage,
		];

		$fedPropTitleText = "Property:$fedPropRemoteId";
		$fedPropDataType = 'string';
		$fedPropConceptURI = "http://wikidata.beta.wmflabs.org/entity/$fedPropRemoteId";
		$this->mockSourceApiRequests( [
			$this->getSiteInfoNamespaceRequestData(),
			[
				$searchRequest,
				[
					'success' => 1,
					'search' => [
						[
							'id' => $fedPropRemoteId,
							'title' => $fedPropTitleText,
							'pageid' => 666,
							'url' => "https://wikidata.beta.wmflabs.org/wiki/Property:$fedPropRemoteId",
							'datatype' => $fedPropDataType,
							// no 'display', pretend this Wikibase predates T104344
							'label' => $fedPropLabel,
							'concepturi' => $fedPropConceptURI,
							'match' => [
								'type' => 'label',
								'language' => 'en',
								'text' => $fedPropLabel,
							],
						],
					],
				],
			],
		] );

		[ $result ] = $this->doApiRequestWithToken( $searchRequest );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertCount( 1, $result['search'] );

		$this->assertSame( $fedPropId->getSerialization(), $result['search'][0]['id'] );
		$this->assertSame( $fedPropLabel, $result['search'][0]['label'] );
		$this->assertSame( $fedPropLabel, $result['search'][0]['display']['label']['value'] );
		// mocked API response doesn’t indicate language of 'label', should fall back to $searchLanguage
		$this->assertSame( $searchLanguage, $result['search'][0]['display']['label']['language'] );
		$this->assertSame( $fedPropConceptURI, $result['search'][0]['concepturi'] );
		$this->assertSame( $fedPropDataType, $result['search'][0]['datatype'] );
		$this->assertSame( $fedPropTitleText, $result['search'][0]['title'] );
		$this->assertSame(
			WikibaseRepo::getSettings()->getSetting( 'federatedPropertiesSourceScriptUrl' )
			. 'index.php?title=' . urlencode( $fedPropTitleText ),
			$result['search'][0]['url']
		);
	}

	private function getSiteInfoNamespaceRequestData(): array {
		return [
			[
				'action' => 'query',
				'meta' => 'siteinfo',
				'siprop' => 'namespaces',
			],
			json_decode(
				file_get_contents( __DIR__ . '/../../../data/federatedProperties/query-siteinfo-namespaces-wikibase.json' ),
				true
			),
		];
	}

}

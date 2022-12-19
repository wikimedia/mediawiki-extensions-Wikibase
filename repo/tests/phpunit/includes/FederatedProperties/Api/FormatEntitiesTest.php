<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use HamcrestPHPUnitIntegration;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Tests\WikibaseTablesUsed;

/**
 * @covers \Wikibase\Repo\Api\FormatEntities
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class FormatEntitiesTest extends FederatedPropertiesApiTestCase {

	use HamcrestPHPUnitIntegration;
	use WikibaseTablesUsed;

	protected function setUp(): void {
		$this->withLocalPropertySource = true;
		$this->markTablesUsedForEntityEditing();

		parent::setUp();
	}

	public function testWithFederatedProperty(): void {
		$remotePid = 'P666';
		$federatedPropertyId = $this->newFederatedPropertyIdFromPId( $remotePid )->getSerialization();
		$expectedLabel = 'potato';

		$this->mockSourceApiRequests( [
			$this->getSiteInfoNamespaceRequestData(),
			$this->getGetEntitiesRequestDataForEntityWithLabel( $remotePid, $expectedLabel ),
		] );

		[ $resultArray ] = $this->doApiRequest( [
			'action' => 'wbformatentities',
			'ids' => $federatedPropertyId,
		] );

		$this->assertArrayHasKey( 'wbformatentities', $resultArray );
		$this->assertArrayHasKey( $federatedPropertyId, $resultArray['wbformatentities'] );

		$this->assertThatHamcrest(
			$resultArray['wbformatentities'][$federatedPropertyId],
			is( htmlPiece( havingRootElement(
				both( havingTextContents( $expectedLabel ) )->andAlso(
					tagMatchingOutline( '<a>' )
				)
			) ) )
		);
	}

	public function testWithLocalAndFederatedProperty(): void {
		$expectedLocalPropLabel = 'local prop';
		$localProperty = new Property( null, new Fingerprint( new TermList( [ new Term( 'en', $expectedLocalPropLabel ) ] ) ), 'string' );
		$this->getEntityStore()->saveEntity( $localProperty, 'test prop', $this->getTestUser()->getUser(), EDIT_NEW );
		$localPropertyId = $localProperty->getId()->getSerialization();

		$remotePid = 'P777';
		$federatedPropertyId = $this->newFederatedPropertyIdFromPId( $remotePid )->getSerialization();
		$expectedFedPropLabel = 'feddy prop';

		$this->mockSourceApiRequests( [
			$this->getSiteInfoNamespaceRequestData(),
			$this->getGetEntitiesRequestDataForEntityWithLabel( $remotePid, $expectedFedPropLabel ),
		] );

		[ $resultArray ] = $this->doApiRequest( [
			'action' => 'wbformatentities',
			'ids' => "$federatedPropertyId|$localPropertyId",
		] );

		$this->assertArrayHasKey( 'wbformatentities', $resultArray );
		$this->assertArrayHasKey( $federatedPropertyId, $resultArray['wbformatentities'] );
		$this->assertArrayHasKey( $localPropertyId, $resultArray['wbformatentities'] );

		$this->assertThatHamcrest(
			$resultArray['wbformatentities'][$federatedPropertyId],
			is( htmlPiece( havingRootElement(
				havingTextContents( $expectedFedPropLabel )
			) ) )
		);
		$this->assertThatHamcrest(
			$resultArray['wbformatentities'][$localPropertyId],
			is( htmlPiece( havingRootElement(
				havingTextContents( $expectedLocalPropLabel )
			) ) )
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

	private function getGetEntitiesRequestDataForEntityWithLabel( string $remotePid, string $enLabel ): array {
		return [
			[
				'action' => 'wbgetentities',
				'ids' => $remotePid,
			],
			[
				'entities' => [
					$remotePid => [
						'labels' => [ 'en' => [ 'language' => 'en', 'value' => $enLabel ] ],
					],
				],
			],
		];
	}

}

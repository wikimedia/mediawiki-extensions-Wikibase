<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ApiEntityLookup {

	/**
	 * @var GenericActionApiClient
	 */
	private $api;

	private $entityLookupResult = [];

	public function __construct( GenericActionApiClient $api ) {
		$this->api = $api;
	}

	/**
	 * @param EntityId[] $entityIds
	 */
	public function fetchEntities( array $entityIds ): void {
		Assert::parameterElementType( EntityId::class, $entityIds, '$entityIds' );

		// Fetch up to 50 entities each time
		$entityIdBatches = array_chunk( $entityIds, 50 );
		foreach ( $entityIdBatches as $entityIdBatch ) {
			$this->entityLookupResult = array_merge(
				$this->entityLookupResult,
				$this->getEntities( $this->getEntitiesToFetch( $entityIdBatch ) )
			);
		}
	}

	/**
	 * @param EntityId[] $entityIds
	 * @return EntityId[]
	 */
	private function getEntitiesToFetch( array $entityIds ): array {
		return array_filter( $entityIds, function ( $id ) {
			return !array_key_exists( $id->getSerialization(), $this->entityLookupResult );
		} );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @return array the json_decoded part of the wbgetentities API response for the entity.
	 */
	private function getEntities( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return [];
		}
		Assert::parameterElementType( EntityId::class, $entityIds, '$entityIds' );

		$response = $this->api->get( [
			'action' => 'wbgetentities',
			'ids' => implode( '|', $entityIds ),
			'props' => 'labels|descriptions|datatype',
			'format' => 'json'
		] );

		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable The API response will be JSON here
		return json_decode( $response->getBody()->getContents(), true )[ 'entities' ];
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return array containing the part of the wbgetentities response for the given entity id
	 */
	public function getResultPartForId( EntityId $entityId ): array {
		if ( !array_key_exists( $entityId->getSerialization(), $this->entityLookupResult ) ) {
			wfDebugLog( 'Wikibase', 'Entity ' . $entityId->getSerialization() . ' not prefetched.' );
			$this->fetchEntities( [ $entityId ] );
		}

		return $this->entityLookupResult[ $entityId->getSerialization() ];
	}

}

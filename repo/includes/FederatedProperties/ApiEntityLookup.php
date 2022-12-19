<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikimedia\Assert\Assert;

/**
 * A class that handles fetching and in-memory caching of entities.
 *
 * @license GPL-2.0-or-later
 */
class ApiEntityLookup {

	/**
	 * @var GenericActionApiClient
	 */
	private $api;

	/**
	 * Local cache used to store the results during one request.
	 * @var array
	 */
	private $lookupCache = [];

	public function __construct( GenericActionApiClient $api ) {
		$this->api = $api;
	}

	/**
	 * Fetches entities from the source and stores the result in cache.
	 * @param FederatedPropertyId[] $ids
	 */
	public function fetchEntities( array $ids ): void {
		Assert::parameterElementType( FederatedPropertyId::class, $ids, '$entityIds' );

		// Fetch up to 50 entities each time
		$entityIdBatches = array_chunk( $ids, 50 );
		foreach ( $entityIdBatches as $entityIdBatch ) {
			$this->lookupCache = array_merge(
				$this->lookupCache,
				$this->getEntities( $this->getEntitiesToFetch( $entityIdBatch ) )
			);
		}
	}

	/**
	 * @param FederatedPropertyId[] $entityIds
	 * @return FederatedPropertyId[]
	 */
	private function getEntitiesToFetch( array $entityIds ): array {
		return array_filter( $entityIds, function ( $id ) {
			return !array_key_exists( $id->getRemoteIdSerialization(), $this->lookupCache );
		} );
	}

	/**
	 * @param FederatedPropertyId[] $ids
	 * @return array the json_decoded part of the wbgetentities API response for the entity.
	 */
	private function getEntities( array $ids ) {
		if ( empty( $ids ) ) {
			return [];
		}
		Assert::parameterElementType( FederatedPropertyId::class, $ids, '$entityIds' );

		$entityIds = $this->getFederatedEntityIdsWithoutPrefix( $ids );

		$response = $this->api->get( [
			'action' => 'wbgetentities',
			'ids' => implode( '|', $entityIds ),
			'props' => 'labels|descriptions|datatype',
			'format' => 'json',
		] );

		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable The API response will be JSON here
		return json_decode( $response->getBody()->getContents(), true )[ 'entities' ];
	}

	/**
	 * Getter for the federated entity result data.
	 * If not currently in cache it will make a new request.
	 * @param FederatedPropertyId $id
	 * @return array containing the part of the wbgetentities response for the given entity id
	 */
	public function getResultPartForId( FederatedPropertyId $id ): array {
		if ( !array_key_exists( $id->getRemoteIdSerialization(), $this->lookupCache ) ) {
			wfDebugLog( 'Wikibase', 'Entity ' . $id->getSerialization() . ' not prefetched.' );
			$this->fetchEntities( [ $id ] );
		}

		return $this->lookupCache[ $id->getRemoteIdSerialization() ];
	}

	/**
	 * @param FederatedPropertyId[] $federatedEntityIds
	 * @return string[]
	 */
	public function getFederatedEntityIdsWithoutPrefix( array $federatedEntityIds ): array {
		return array_map( function ( FederatedPropertyId $id ): string {
			return $id->getRemoteIdSerialization();
		}, $federatedEntityIds );
	}

}

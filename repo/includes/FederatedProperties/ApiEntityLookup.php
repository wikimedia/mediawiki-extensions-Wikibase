<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityId;
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
	 * @param EntityId[] $entityIds
	 */
	public function fetchEntities( array $entityIds ): void {
		Assert::parameterElementType( EntityId::class, $entityIds, '$entityIds' );

		// Fetch up to 50 entities each time
		$entityIdBatches = array_chunk( $entityIds, 50 );
		foreach ( $entityIdBatches as $entityIdBatch ) {
			$this->lookupCache = array_merge(
				$this->lookupCache,
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
			return !array_key_exists( $id->getSerialization(), $this->lookupCache );
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
	 * Getter for the the federated entity result data.
	 * If not currently in cache it will make a new request.
	 * @param EntityId $entityId
	 * @return array containing the part of the wbgetentities response for the given entity id
	 */
	public function getResultPartForId( EntityId $entityId ): array {
		if ( !array_key_exists( $entityId->getSerialization(), $this->lookupCache ) ) {
			wfDebugLog( 'Wikibase', 'Entity ' . $entityId->getSerialization() . ' not prefetched.' );
			$this->fetchEntities( [ $entityId ] );
		}

		return $this->lookupCache[ $entityId->getSerialization() ];
	}

}

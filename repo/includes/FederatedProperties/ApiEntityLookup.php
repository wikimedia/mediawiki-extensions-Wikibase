<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikimedia\Assert\Assert;

/**
 * A class that handles fetching and in-memory caching of entities.
 *
 * @license GPL-2.0-or-later
 */
class ApiEntityLookup {

	/**
	 * @var EntitySourceLookup
	 */
	private $entitySourceLookup;

	/**
	 * @var GenericActionApiClient
	 */
	private $api;

	/**
	 * Local cache used to store the results during one request.
	 * @var array
	 */
	private $lookupCache = [];

	public function __construct( GenericActionApiClient $api, EntitySourceLookup $entitySourceLookup ) {
		$this->api = $api;
		$this->entitySourceLookup = $entitySourceLookup;
	}

	/**
	 * Fetches entities from the source and stores the result in cache.
	 * @param EntityId[] $entityIds
	 */
	public function fetchEntities( array $entityIds ): void {
		Assert::parameterElementType( FederatedPropertyId::class, $entityIds, '$entityIds' );

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
			return !array_key_exists( $this->stripConceptBaseUriFromId( $id ), $this->lookupCache );
		} );
	}

	/**
	 * @param EntityId[] $federatedEntityIds
	 * @return array the json_decoded part of the wbgetentities API response for the entity.
	 */
	private function getEntities( array $federatedEntityIds ) {
		if ( empty( $federatedEntityIds ) ) {
			return [];
		}
		Assert::parameterElementType( FederatedPropertyId::class, $federatedEntityIds, '$entityIds' );

		$entityIds = $this->getFederatedEntityIdsWithoutPrefix( $federatedEntityIds );

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
	 * Getter for the federated entity result data.
	 * If not currently in cache it will make a new request.
	 * @param EntityId $federatedEntityId
	 * @return array containing the part of the wbgetentities response for the given entity id
	 */
	public function getResultPartForId( EntityId $federatedEntityId ): array {
		if ( !array_key_exists( $this->stripConceptBaseUriFromId( $federatedEntityId ), $this->lookupCache ) ) {
			wfDebugLog( 'Wikibase', 'Entity ' . $federatedEntityId->getSerialization() . ' not prefetched.' );
			$this->fetchEntities( [ $federatedEntityId ] );
		}

		return $this->lookupCache[ $this->stripConceptBaseUriFromId( $federatedEntityId ) ];
	}

	/**
	 * @param EntityId[] $federatedEntityIds
	 * @return string[]
	 */
	public function getFederatedEntityIdsWithoutPrefix( array $federatedEntityIds ): array {
		return array_map( [ $this, 'stripConceptBaseUriFromId' ], $federatedEntityIds );
	}

	/**
	 * @param EntityId $id
	 * @return string
	 */
	private function stripConceptBaseUriFromId( EntityId $id ): string {
		return str_replace(
			$this->entitySourceLookup->getEntitySourceById( $id )->getConceptBaseUri(),
			'',
			$id->getSerialization()
		);
	}
}

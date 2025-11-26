<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use MediaWiki\Http\HttpRequestFactory;
use Wikibase\DataAccess\EntitySourceDefinitions;

/**
 * Retrieves full remote entity JSON via wbgetentities and stores it
 * in a DB-backed mirror (RemoteEntityStore).
 *
 * Uses EntitySourceDefinitions to look up API URLs for remote sources
 * based on the concept URI's base.
 */
class RemoteEntityLookup {

	private HttpRequestFactory $httpRequestFactory;
	private EntitySourceDefinitions $entitySourceDefinitions;
	private RemoteEntityStore $store;

	public function __construct(
		HttpRequestFactory $httpRequestFactory,
		EntitySourceDefinitions $entitySourceDefinitions,
		RemoteEntityStore $store
	) {
		$this->httpRequestFactory = $httpRequestFactory;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->store = $store;
	}

	/**
	 * Get entity data, fetching from remote and storing in DB if not already cached.
	 *
	 * Use this method when you need to ensure the entity is persisted
	 * (e.g., when saving a statement that references this entity).
	 *
	 * @param string $conceptUri e.g. "https://www.wikidata.org/entity/Q42"
	 * @return array|null Decoded wbgetentities entity or null on failure
	 */
	public function getEntity( string $conceptUri ): ?array {
		// 1) Try DB mirror first
		\wfDebugLog( 'federation', "RemoteEntityLookup::getEntity uri={$conceptUri} - checking DB mirror" );

		$cached = $this->store->get( $conceptUri );
		if ( $cached !== null ) {
			\wfDebugLog( 'federation', "RemoteEntityLookup::getEntity uri={$conceptUri} - DB HIT" );
			return $cached;
		}

		\wfDebugLog( 'federation', "RemoteEntityLookup::getEntity uri={$conceptUri} - DB MISS, calling remote" );

		// 2) Fetch from remote
		$entityData = $this->fetchFromRemote( $conceptUri );
		if ( $entityData === null ) {
			return null;
		}

		// 3) Store in DB mirror for subsequent calls
		$this->store->set( $conceptUri, $entityData );

		\wfDebugLog(
			'federation',
			"RemoteEntityLookup::getEntity uri={$conceptUri} - stored in DB mirror"
		);

		return $entityData;
	}

	/**
	 * Fetch entity data from the remote API.
	 *
	 * @param string $conceptUri e.g. "https://www.wikidata.org/entity/Q42"
	 * @return array|null Decoded wbgetentities entity or null on failure
	 */
	private function fetchFromRemote( string $conceptUri ): ?array {
		$entityId = basename( $conceptUri );

		$apiUrl = $this->getApiUrlForConceptUri( $conceptUri );
		if ( $apiUrl === null ) {
			return null;
		}

		$params = [
			'action'      => 'wbgetentities',
			'format'      => 'json',
			'errorformat' => 'plaintext',
			'ids'         => $entityId,
			'props'       => 'info|labels|descriptions|aliases|claims',
		];

		$remoteUrl = $apiUrl . '?' . \wfArrayToCgi( $params );

		$req = $this->httpRequestFactory->create( $remoteUrl, [
			'method'  => 'GET',
			'timeout' => 10,
		] );

		$status = $req->execute();
		if ( !$status->isOK() ) {
			return null;
		}

		$resp = \FormatJson::decode( $req->getContent(), true );
		if ( !is_array( $resp ) || !isset( $resp['entities'][$entityId] ) ) {
			return null;
		}

		return $resp['entities'][$entityId];
	}

	/**
	 * Get the API URL for a given concept URI by looking up the matching API entity source.
	 *
	 * @param string $conceptUri e.g. "https://www.wikidata.org/entity/Q42"
	 * @return string|null Full action API URL, e.g. "https://www.wikidata.org/w/api.php"
	 */
	private function getApiUrlForConceptUri( string $conceptUri ): ?string {
		$source = $this->entitySourceDefinitions->getApiSourceForConceptUri( $conceptUri );

		if ( $source !== null ) {
			return $source->getRepoApiUrl();
		}

		return null;
	}
}

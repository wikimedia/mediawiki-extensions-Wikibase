<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use MediaWiki\Http\HttpRequestFactory;
use Wikibase\Lib\SettingsArray;

/**
 * Retrieves full remote entity JSON via wbgetentities and stores it
 * in a DB-backed mirror (RemoteEntityStore).
 *
 * Settings:
 *  - federationRepositories (array<string,string>) map repoName => action API URL
 *      e.g. [ 'wikidata' => 'https://www.wikidata.org/w/api.php' ]
 *  - federationEntityCacheTTL (int, seconds) – used by RemoteEntityStore
 */
class RemoteEntityLookup {

	private HttpRequestFactory $httpRequestFactory;
	private SettingsArray $settings;
	private RemoteEntityStore $store;

	public function __construct(
		HttpRequestFactory $httpRequestFactory,
		SettingsArray $settings,
		RemoteEntityStore $store
	) {
		$this->httpRequestFactory = $httpRequestFactory;
		$this->settings = $settings;
		$this->store = $store;
	}

	/**
	 * @param string $repository Logical repository name (e.g. "wikidata")
	 * @param string $entityId   e.g. "Q42"
	 * @return array|null Decoded wbgetentities entity or null on failure
	 */
	public function getEntity( string $repository, string $entityId ): ?array {
		// 1) Try DB mirror first
		\wfDebugLog(
			'federation',
			"RemoteEntityLookup::getEntity repo={$repository} id={$entityId} - checking DB mirror"
		);

		$cached = $this->store->get( $repository, $entityId );
		if ( $cached !== null ) {
			\wfDebugLog(
				'federation',
				"RemoteEntityLookup::getEntity repo={$repository} id={$entityId} - DB HIT"
			);

			return $cached;
		}

		\wfDebugLog(
			'federation',
			"RemoteEntityLookup::getEntity repo={$repository} id={$entityId} - DB MISS, calling remote"
		);

		// 2) Fetch from remote
		$apiUrl = $this->getApiUrlForRepository( $repository );
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

		$entityData = $resp['entities'][$entityId];

		// 3) Store in DB mirror for subsequent calls
		$this->store->set( $repository, $entityId, $entityData );

		\wfDebugLog(
			'federation',
			"RemoteEntityLookup::getEntity repo={$repository} id={$entityId} - stored in DB mirror"
		);

		return $entityData;
	}

	/**
	 * @return string|null Full action API URL, e.g. "https://www.wikidata.org/w/api.php"
	 */
	private function getApiUrlForRepository( string $repository ): ?string {
		if ( $this->settings->hasSetting( 'federationRepositories' ) ) {
			$repos = $this->settings->getSetting( 'federationRepositories' );
			if ( is_array( $repos ) && isset( $repos[$repository] ) && is_string( $repos[$repository] ) ) {
				return $repos[$repository];
			}
		}

		// MVP fallback
		if ( $repository === 'wikidata' ) {
			return 'https://www.wikidata.org/w/api.php';
		}

		return null;
	}
}

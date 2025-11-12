<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use Wikibase\Lib\SettingsArray;
use Wikimedia\Rdbms\LBFactory;

/**
 * DB-backed cache / mirror for remote entities.
 *
 * Table: wb_remote_entity
 *  - re_concept_uri (PK)
 *  - re_touched     (MW timestamp)
 *  - re_blob        (JSON blob from wbgetentities)
 *
 * TTL is controlled by the federationEntityCacheTTL setting (seconds).
 */
class RemoteEntityStore {

	private LBFactory $lbFactory;
	private SettingsArray $settings;

	public function __construct(
		LBFactory $lbFactory,
		SettingsArray $settings
	) {
		$this->lbFactory = $lbFactory;
		$this->settings = $settings;
	}

	/**
	 * @return int|null TTL in seconds, or null if disabled
	 */
	private function getTtl(): ?int {
		if ( !$this->settings->hasSetting( 'federationEntityCacheTTL' ) ) {
			return null;
		}

		$ttl = $this->settings->getSetting( 'federationEntityCacheTTL' );
		if ( !is_int( $ttl ) || $ttl <= 0 ) {
			return null;
		}

		return $ttl;
	}

	/**
	 * @return string|null cutoff timestamp in TS_MW, or null if no TTL
	 */
	private function getExpiryCutoff(): ?string {
		$ttl = $this->getTtl();
		if ( $ttl === null ) {
			return null;
		}

		$cutoffUnix = time() - $ttl;

		return \wfTimestamp( TS_MW, $cutoffUnix );
	}

	/**
	 * @return array|null decoded entity blob, or null on miss/expired/bad data
	 */
	public function get( string $conceptUri ): ?array {
		$db = $this->lbFactory->getReplicaDatabase();

		$row = $db->selectRow(
			'wb_remote_entity',
			[ 're_blob', 're_touched' ],
			[ 're_concept_uri' => $conceptUri ],
			__METHOD__
		);

		if ( !$row ) {
			return null;
		}

		$cutoff = $this->getExpiryCutoff();
		if ( $cutoff !== null && $row->re_touched < $cutoff ) {
			// Expired – treat as miss so RemoteEntityLookup will refetch.
			return null;
		}

		$data = \FormatJson::decode( $row->re_blob, true );
		if ( !is_array( $data ) ) {
			return null;
		}

		return $data;
	}

	/**
	 * @param array $entityData decoded wbgetentities entity blob
	 */
	public function set( string $conceptUri, array $entityData ): void {
		$dbw = $this->lbFactory->getPrimaryDatabase();

		$blob = \FormatJson::encode( $entityData, false, \FormatJson::ALL_OK );
		$touched = $dbw->timestamp( \wfTimestampNow() );

		$dbw->upsert(
			'wb_remote_entity',
			[
				're_concept_uri' => $conceptUri,
				're_touched'     => $touched,
				're_blob'        => $blob,
			],
			[ 're_concept_uri' ],
			[
				're_touched' => $touched,
				're_blob'    => $blob,
			],
			__METHOD__
		);
	}

	public function delete( string $conceptUri ): void {
		$dbw = $this->lbFactory->getPrimaryDatabase();

		$dbw->delete(
			'wb_remote_entity',
			[ 're_concept_uri' => $conceptUri ],
			__METHOD__
		);
	}
}

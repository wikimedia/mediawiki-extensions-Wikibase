<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityRevisionLookup implements EntityRevisionLookup {

	// EntityRevisionLookup[] entity type (string) => EntityRevisionLokup instance
	private $lookups;

	public function __construct( array $lookups ) {
		// TODO: validate $lookups
		$this->lookups = $lookups;
	}

	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_REPLICA
	) {
		$lookup = $this->getLookupForEntity( $entityId );

		return $lookup->getEntityRevision( $entityId, $revisionId, $mode );
	}

	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_REPLICA ) {
		$lookup = $this->getLookupForEntity( $entityId );

		return $lookup->getLatestRevisionId( $entityId, $mode );
	}

	/**
	 * @param EntityId $entityId
	 * @return EntityRevisionLookup|null
	 */
	private function getLookupForEntity( EntityId $entityId ) {
		$entityType = $entityId->getEntityType();
		if ( array_key_exists( $entityType, $this->lookups ) ) {
			return $this->lookups[$entityType];
		}

		// TODO: throw exception on unhandled entity type?
		return null;
	}

}

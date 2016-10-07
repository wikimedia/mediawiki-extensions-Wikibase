<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;

class MockEntityRevisionLookup implements EntityRevisionLookup {

	private $entities;

	private $maxRevisionId = 0;

	public function addEntity( EntityDocument $entity, $revisionId = 0, $timestamp = 0 ) {
		if ( $revisionId === 0 ) {
			$revisionId = ++$this->maxRevisionId;
		}
		$this->maxRevisionId = max( $this->maxRevisionId, $revisionId );

		$key = $entity->getId()->getSerialization();

		$revision = new EntityRevision( $entity, $revisionId, wfTimestamp( TS_MW, $timestamp ) );

		$this->entities[$key][$revisionId] = $revision;
	}

	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_SLAVE
	) {
		$key = $entityId->getSerialization();

		if ( empty( $this->entities[$key] ) ) {
			return null;
		}

		$revisions = $this->entities[$key];

		if ( $revisionId === 0 ) {
			$revisionId = end( array_keys( $revisions ) );
		}

		if ( !isset( $revisions[$revisionId] ) ) {
			throw new StorageException( "No such revision for entity $key: $revisionId" );
		}

		return $revisions[$revisionId];
	}

	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_SLAVE ) {
		$revision = $this->getEntityRevision( $entityId, 0, $mode );
		return $revision !== null ? $revision->getRevisionId() : false;
	}

}

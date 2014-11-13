<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;

/**
 * An implementation of EntityLookup based on an EntityRevisionLookup.
 *
 * This implementation does not resolve redirects.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RevisionBasedEntityLookup implements EntityLookup {

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	/**
	 * @param EntityRevisionLookup $lookup
	 */
	public function __construct( EntityRevisionLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException
	 * @return Entity|null
	 */
	public function getEntity( EntityId $entityId ) {
		$revision = $this->lookup->getEntityRevision( $entityId );
		return ( $revision === null ) ? null : $revision->getEntity();
	}

	/**
	 * @see EntityLookup::hasEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		$latest = $this->lookup->getLatestRevisionId( $entityId );
		return $latest !== false;

	}

}

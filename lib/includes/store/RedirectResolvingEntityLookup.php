<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Implementation of EntityLookup that opaquely resolves one level
 * of redirects when looking up entities.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RedirectResolvingEntityLookup implements EntityLookup {

	/**
	 * @var EntityLookup
	 */
	protected $lookup;

	/**
	 * Resolver for calling getEntity.
	 *
	 * @var EntityRedirectResolver
	 */
	protected $getEntityResolver = null;

	/**
	 * Resolver for calling hasEntity.
	 *
	 * @var EntityRedirectResolver
	 */
	protected $hasEntityResolver = null;

	/**
	 * @param EntityLookup $lookup The lookup to use
	 */
	public function __construct( EntityLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * If the given entity ID points to a redirect, that redirect is resolved and the
	 * target entity returned.
	 *
	 * Callers can detect the presence of a redirect by comparing the ID of the returned
	 * Entity with the request ID.
	 *
	 * @param EntityId $entityId
	 *
	 * @throw StorageException
	 * @return Entity|null
	 */
	public function getEntity( EntityId $entityId ) {
		if ( !$this->getEntityResolver ) {
			$this->getEntityResolver = new EntityRedirectResolver( array( $this->lookup, __FUNCTION__ ) );
		}

		return $this->getEntityResolver->apply( $entityId );
	}

	/**
	 * @see EntityLookup::hasEntity
	 *
	 * If the given entity ID points to a redirect, that redirect is resolved and the
	 * existence of the target entity is checked.
	 *
	 * @param EntityId $entityId
	 *
	 * @throw StorageException
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		if ( !$this->hasEntityResolver ) {
			$this->hasEntityResolver = new EntityRedirectResolver( array( $this->lookup, __FUNCTION__ ) );
		}

		return $this->hasEntityResolver->apply( $entityId );
	}

}

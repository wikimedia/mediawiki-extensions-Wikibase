<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Implementation of EntityLookup that opaquely resolves one level
 * of redirects when looking up entities.
 *
 * @since 2.0
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RedirectResolvingEntityLookup implements EntityLookup {

	/**
	 * An EntityRedirectResolvingDecorator wrapping and emulating a EntityLookup.
	 *
	 * @note This does not formally implement EntityLookup!
	 *
	 * @var EntityLookup
	 */
	private $lookup;

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
	 * @return EntityDocument|null
	 * @throws EntityLookupException
	 */
	public function getEntity( EntityId $entityId ) {
		try {
			return $this->lookup->getEntity( $entityId );
		} catch ( UnresolvedEntityRedirectException $ex ) {
			return $this->lookup->getEntity( $ex->getRedirectTargetId() );
		}
	}

	/**
	 * @see EntityLookup::hasEntity
	 *
	 * If the given entity ID points to a redirect, that redirect is resolved and the
	 * existence of the target entity is checked.
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool
	 * @throws EntityLookupException
	 */
	public function hasEntity( EntityId $entityId ) {
		try {
			return $this->lookup->hasEntity( $entityId );
		} catch ( UnresolvedEntityRedirectException $ex ) {
			return $this->lookup->hasEntity( $ex->getRedirectTargetId() );
		}
	}

}

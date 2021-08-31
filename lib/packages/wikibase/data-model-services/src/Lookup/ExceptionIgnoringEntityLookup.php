<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * An {@link EntityLookup} which ignores any exceptions
 * which occur while retrieving an entity
 * and instead pretends the entity does not exist.
 *
 * @license GPL-2.0-or-later
 */
class ExceptionIgnoringEntityLookup implements EntityLookup {

	/**
	 * @var EntityLookup
	 */
	private $lookup;

	public function __construct( EntityLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * Attempt to retrieve the entity,
	 * returning `null` if any errors occur.
	 *
	 * @param EntityId $entityId
	 * @return EntityDocument|null
	 */
	public function getEntity( EntityId $entityId ) {
		try {
			return $this->lookup->getEntity( $entityId );
		} catch ( EntityLookupException $exception ) {
			return null;
		}
	}

	/**
	 * Returns whether the given entity can potentially be looked up using {@link getEntity()}.
	 * Note that this does not guarantee {@link getEntity()} will return an {@link EntityDocument} –
	 * it may still return `null` if an error occurs retrieving the entity.
	 *
	 * @param EntityId $entityId
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		return $this->lookup->hasEntity( $entityId );
	}

}

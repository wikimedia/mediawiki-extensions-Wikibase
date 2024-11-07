<?php

namespace Wikibase\DataModel\Services\Lookup;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * EntityLookup that counts how many entities have been loaded through it and throws
 * an exception once to many entities have been loaded.
 *
 * This is needed to limit the number of entities that can be loaded via some
 * user controlled features, like entity access in Lua.
 *
 * @since 2.0
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class RestrictedEntityLookup implements EntityLookup {

	private EntityLookup $entityLookup;
	private int $entityAccessLimit;

	/** @var array<string,true> Entity id serialization => true */
	private array $entitiesAccessed = [];

	public function __construct( EntityLookup $entityLookup, int $entityAccessLimit ) {
		if ( $entityAccessLimit < 1 ) {
			throw new InvalidArgumentException( '$entityAccessLimit must be a positive integer' );
		}

		$this->entityLookup = $entityLookup;
		$this->entityAccessLimit = $entityAccessLimit;
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws EntityAccessLimitException
	 * @return EntityDocument
	 */
	public function getEntity( EntityId $entityId ) {
		$entityIdSerialization = $entityId->getSerialization();

		if ( !array_key_exists( $entityIdSerialization, $this->entitiesAccessed ) ) {
			$this->entitiesAccessed[$entityIdSerialization] = true;

			if ( count( $this->entitiesAccessed ) > $this->entityAccessLimit ) {
				throw new EntityAccessLimitException(
					$entityId,
					'Too many entities loaded, must not load more than ' . $this->entityAccessLimit . ' entities.'
				);
			}
		}

		return $this->entityLookup->getEntity( $entityId );
	}

	/**
	 * @see EntityLookup::hasEntity
	 *
	 * @throws EntityLookupException
	 */
	public function hasEntity( EntityId $entityId ): bool {
		return $this->entityLookup->hasEntity( $entityId );
	}

	/**
	 * Returns the number of entities already loaded via this object.
	 *
	 * @since 2.0
	 */
	public function getEntityAccessCount(): int {
		return count( $this->entitiesAccessed );
	}

	/**
	 * Resets the number and list of entities loaded via this object.
	 *
	 * @since 3.4
	 */
	public function reset(): void {
		$this->entitiesAccessed = [];
	}

	/**
	 * Whether an entity has been accessed before via this RestrictedEntityLookup.
	 *
	 * @since 2.0
	 */
	public function entityHasBeenAccessed( EntityId $entityId ): bool {
		$entityIdSerialization = $entityId->getSerialization();

		return array_key_exists( $entityIdSerialization, $this->entitiesAccessed );
	}

}

<?php

namespace Wikibase\Client\DataAccess;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\StorageException;
use Wikimedia\Assert\Assert;

/**
 * EntityLookup that counts how many entities have been loaded through it and throws
 * an exception once to many entities have been loaded.
 *
 * This is needed to limit the number of entities that can be loaded via some
 * user controlled features, like entity access in Lua.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class RestrictedEntityLookup implements EntityLookup {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var int
	 */
	private $entityAccessLimit;

	/**
	 * @var bool[] Entity id serialization => bool
	 */
	private $entitiesAccessed = array();

	/**
	 * @var int
	 */
	private $entityAccessCount = 0;

	/**
	 * @param EntityLookup $entityLookup
	 * @param int $entityAccessLimit
	 */
	public function __construct( EntityLookup $entityLookup, $entityAccessLimit ) {
		Assert::parameterType( 'integer', $entityAccessLimit, '$entityAccessLimit' );

		$this->entityLookup = $entityLookup;
		$this->entityAccessLimit = $entityAccessLimit;
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException|EntityAccessLimitException
	 * @return EntityDocument|null
	 */
	public function getEntity( EntityId $entityId ) {
		$entityIdSerialization = $entityId->getSerialization();

		if ( !array_key_exists( $entityIdSerialization, $this->entitiesAccessed ) ) {
			$this->entityAccessCount++;
			$this->entitiesAccessed[$entityIdSerialization] = true;
		}

		if ( $this->entityAccessCount > $this->entityAccessLimit ) {
			throw new EntityAccessLimitException(
				'To many entities loaded, must not load more than ' . $this->entityAccessLimit . ' entities.'
			);
		}

		return $this->entityLookup->getEntity( $entityId );
	}

	/**
	 * @see EntityLookup::hasEntity
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		return $this->entityLookup->hasEntity( $entityId );
	}

	/**
	 * Returns the number of entities already loaded via this object.
	 *
	 * @return int
	 */
	public function getEntityAccessCount() {
		return $this->entityAccessCount;
	}

	/**
	 * Whether an entity has been accessed before via this RestrictedEntityLookup.
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	public function entityHasBeenAccessed( EntityId $entityId ) {
		$entityIdSerialization = $entityId->getSerialization();

		return array_key_exists( $entityIdSerialization, $this->entitiesAccessed );
	}

}

<?php

namespace Wikibase;

use InvalidArgumentException;
use OutOfBoundsException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Assert\Assert;

/**
 * Factory for Entity objects.
 *
 * @deprecated
 * This class makes many assumptions that do not hold, including
 * - all entities can be constructed empty
 * - only Items and Properties exist
 * - all entities can construct themselves from their serialization
 * Not a single method is non-problematic, so you should not use this class at all.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityFactory {

	/**
	 * @var callable[] Maps entity types to callbacks to create an instance the respective entity.
	 */
	private $callbacks;

	/**
	 * @since 0.5
	 *
	 * @param callable[] $callbacks Maps entity types to callbacks to create an instance the respective entity.
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $callbacks ) {
		Assert::parameterElementType( 'callable', $callbacks, '$callbacks' );

		$this->callbacks = $callbacks;
	}

	/**
	 * Returns the type identifiers of the entities.
	 *
	 * @since 0.2
	 *
	 * @return array all available type identifiers
	 */
	public function getEntityTypes() {
		return array_keys( $this->callbacks );
	}

	/**
	 * Predicate if the provided string is a valid entity type identifier.
	 *
	 * @since 0.2
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function isEntityType( $type ) {
		return array_key_exists( $type, $this->callbacks );
	}

	/**
	 * Creates a new empty entity of the given type.
	 *
	 * @since 0.3
	 *
	 * @param String $entityType The type of the desired new entity.
	 *
	 * @throws OutOfBoundsException
	 * @return EntityDocument The new Entity object.
	 */
	public function newEmpty( $entityType ) {
		if ( !isset( $this->callbacks[$entityType] ) ) {
			throw new OutOfBoundsException( 'Unknown entity type ' . $entityType );
		}

		$entity = call_user_func( $this->callbacks[$entityType] );

		Assert::postcondition(
			$entity instanceof EntityDocument && $entity->getType() === $entityType,
			'Callback returned no entity or entity of wrong type'
		);

		return $entity;
	}

}

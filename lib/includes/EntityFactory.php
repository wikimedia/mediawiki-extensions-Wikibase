<?php

namespace Wikibase;

use OutOfBoundsException;
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
	 * @var callback[] Maps entity types to classes implementing the respective entity.
	 */
	private $instantiators;

	/**
	 * @since 0.5
	 *
	 * @param callback[] $instantiators Maps entity types to instantiator callbacks.
	 */
	public function __construct( array $instantiators ) {
		$this->instantiators = $instantiators;
	}

	/**
	 * Returns the type identifiers of the entities.
	 *
	 * @since 0.2
	 *
	 * @return array all available type identifiers
	 */
	public function getEntityTypes() {
		return array_keys( $this->instantiators );
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
		return array_key_exists( $type, $this->instantiators );
	}

	/**
	 * Returns the class implementing the given entity type.
	 *
	 * @param string $type
	 *
	 * @throws OutOfBoundsException
	 * @return string callable
	 */
	private function getEntityInstantiator( $type ) {
		if ( !isset( $this->instantiators[$type] ) ) {
			throw new OutOfBoundsException( 'Unknown entity type ' . $type );
		}

		return $this->instantiators[$type];
	}

	/**
	 * Creates a new empty entity of the given type.
	 *
	 * @since 0.3
	 *
	 * @param String $entityType The type of the desired new entity.
	 *
	 * @return EntityDocument The new Entity object.
	 */
	public function newEmpty( $entityType ) {
		$instantiator = $this->getEntityInstantiator( $entityType );

		$entity = call_user_func( $instantiator );
		Assert::postcondition(
			$entity instanceof EntityDocument,
			'Instantiator callback for ' . $entityType . ' did not return an Entity.'
		);

		return $entity;
	}

}

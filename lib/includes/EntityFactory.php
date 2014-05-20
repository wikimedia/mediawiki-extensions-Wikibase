<?php

namespace Wikibase;

use MWException;
use OutOfBoundsException;

/**
 * Factory for Entity objects.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityFactory {

	/**
	 * @var array Maps entity types to classes implementing the respective entity.
	 */
	private $typeMap;

	/**
	 * @since 0.5
	 *
	 * @param array $typeToClass Maps entity types to classes implementing the respective entity.
	 */
	public function __construct( array $typeToClass ) {
		$this->typeMap = $typeToClass;
	}

	/**
	 * @since 0.2
	 *
	 * @deprecated Use WikibaseRepo::getEntityFactory() resp. WikibaseClient::getEntityFactory()
	 *
	 * @return EntityFactory
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$typeToClass = array(
				Item::ENTITY_TYPE => '\Wikibase\Item',
				Property::ENTITY_TYPE => '\Wikibase\Property',
			);

			$instance = new static( $typeToClass );
		}

		return $instance;
	}

	/**
	 * Returns the type identifiers of the entities.
	 *
	 * @since 0.2
	 *
	 * @return array all available type identifiers
	 */
	public function getEntityTypes() {
		return array_keys( $this->typeMap );
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
		return array_key_exists( $type, $this->typeMap );
	}

	/**
	 * Returns the class implementing the given entity type.
	 *
	 * @since 0.5
	 *
	 * @param string $type
	 *
	 * @throws OutOfBoundsException
	 * @return string Class
	 */
	private function getEntityClass( $type ) {
		if ( !isset( $this->typeMap[$type] ) ) {
			throw new OutOfBoundsException( 'Unknown entity type ' . $type );
		}

		return $this->typeMap[$type];
	}

	/**
	 * Creates a new empty entity of the given type.
	 *
	 * @since 0.3
	 *
	 * @param String $entityType The type of the desired new entity.
	 *
	 * @throws MWException if the given entity type is not known.
	 * @return Entity The new Entity object.
	 */
	public function newEmpty( $entityType ) {
		$class = $this->getEntityClass( $entityType );
		return $class::newEmpty();
	}

	/**
	 * Creates a new entity of the given type.
	 *
	 * @since 0.3
	 *
	 * @param String $entityType The type of the desired new entity.
	 * @param array $data An array structure representing the Entity.
	 *
	 * @return Entity The new Entity object.
	 */
	public function newFromArray( $entityType, $data ) {
		$class = $this->getEntityClass( $entityType );
		return $class::newFromArray( $data );
	}

}

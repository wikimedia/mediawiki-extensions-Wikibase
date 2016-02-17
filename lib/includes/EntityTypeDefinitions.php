<?php

namespace Wikibase\Lib;

/**
 * Service that manages entity type definition. This is a registry that provides access to factory
 * functions for various services associated with entity types, such as serializers.
 *
 * EntityTypeDefinitions provides a one-stop interface for defining entity types.
 * Each entity type is defined using a "entity type definition" array.
 * A definition array has the following fields:
 * - serializer-factory-callback: a callback for creating a serializer for entities of this type
 *   (requires a SerializerFactory to be passed to it)
 * - deserializer-factory-callback: a callback for creating a deserializer for entities of this type
 *   (requires a DeserializerFactory to be passed to it)
 * - change-factory-callback: a callback for creating a change object
 *   (requires a fields array to be passed to it)
 *
 * @see docs/entitytypes.wiki
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityTypeDefinitions {

	/**
	 * @var DefinitionsMap
	 */
	private $entityTypeDefinitions;

	/**
	 * @param array[] $entityTypeDefinitions An associative array mapping entity type to entity
	 * definitions. Each entity type definitions are associative arrays, refer to the class level
	 * documentation for details.
	 */
	public function __construct( array $entityTypeDefinitions ) {
		$this->entityTypeDefinitions = new DefinitionsMap( $entityTypeDefinitions );
	}

	/**
	 * @return callable[]
	 */
	public function getSerializerFactoryCallbacks() {
		return $this->entityTypeDefinitions->getMapForDefinitionField( 'serializer-factory-callback' );
	}

	/**
	 * @return callable[]
	 */
	public function getDeserializerFactoryCallbacks() {
		return $this->entityTypeDefinitions->getMapForDefinitionField( 'deserializer-factory-callback' );
	}

	/**
	 * @return callable[]
	 */
	public function getEntityFactoryCallbacks() {
		return $this->entityTypeDefinitions->getMapForDefinitionField( 'entity-factory-callback' );
	}

	/**
	 * @return callable[]
	 */
	public function getChangeFactoryCallbacks() {
		return $this->entityTypeDefinitions->getMapForDefinitionField( 'change-factory-callback' );
	}

	/**
	 * @return string[]
	 */
	public function getContentModelMapping() {
		return $this->entityTypeDefinitions->getMapForDefinitionField( 'content-model' );
	}

}

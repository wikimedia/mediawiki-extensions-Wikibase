<?php

namespace Wikibase\Lib;

/**
 * Service that manages entity type definition. This is a registry that provides access to factory
 * functions for various services associated with entity types, such as serializers.
 *
 * @todo more documentation
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
		$this->entityTypeDefinitions = $entityTypeDefinitions;
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

}

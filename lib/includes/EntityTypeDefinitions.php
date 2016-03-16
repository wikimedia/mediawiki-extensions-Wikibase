<?php

namespace Wikibase\Lib;

use InvalidArgumentException;

/**
 * Service that manages entity type definition. This is a registry that provides access to factory
 * functions for various services associated with entity types, such as serializers.
 *
 * EntityTypeDefinitions provides a one-stop interface for defining entity types.
 * Each entity type is defined using a "entity type definition" array.
 *
 * A definition array has the following fields:
 * - serializer-factory-callback: a callback for creating a serializer for entities of this type
 *   (requires a SerializerFactory to be passed to it)
 * - deserializer-factory-callback: a callback for creating a deserializer for entities of this type
 *   (requires a DeserializerFactory to be passed to it)
 * - view-factory-callback: a callback for creating a view for entities of this type (requires a
 *   language code, a LabelDescriptionLookup, a LanguageFallbackChain and an EditSectionGenerator)
 * - content-model-id: a string used as the content model identifier
 * - content-handler-factory-callback: a callback for creating a content handler dealing with
 *   entities of this type
 * - edit-entity-handler-factory-callback: a callback for creating a handler that can be used in
 *   the wbeditentity api module
 *
 * @see docs/entitytypes.wiki
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityTypeDefinitions {

	/**
	 * @var array[]
	 */
	private $entityTypeDefinitions;

	/**
	 * @param array[] $entityTypeDefinitions Map from entity types to entity definitions
	 *        See class level documentation for details
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $entityTypeDefinitions ) {
		foreach ( $entityTypeDefinitions as $id => $def ) {
			if ( !is_string( $id ) || !is_array( $def ) ) {
				throw new InvalidArgumentException( '$entityTypeDefinitions must be a map from string to arrays' );
			}
		}

		$this->entityTypeDefinitions = $entityTypeDefinitions;
	}

	/**
	 * @return string[] a list of all defined entity types
	 */
	public function getEntityTypes() {
		return array_keys( $this->entityTypeDefinitions );
	}

	/**
	 * @param string $field
	 *
	 * @return mixed
	 */
	private function getMapForDefinitionField( $field ) {
		$fieldValues = array();

		foreach ( $this->entityTypeDefinitions as $id => $def ) {
			if ( isset( $def[$field] ) ) {
				$fieldValues[$id] = $def[$field];
			}
		}

		return $fieldValues;
	}

	/**
	 * @return callable[]
	 */
	public function getSerializerFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'serializer-factory-callback' );
	}

	/**
	 * @return callable[]
	 */
	public function getDeserializerFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'deserializer-factory-callback' );
	}

	/**
	 * @return callable[]
	 */
	public function getViewFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'view-factory-callback' );
	}

	/**
	 * @return string[]
	 */
	public function getContentModelIds() {
		return $this->getMapForDefinitionField( 'content-model-id' );
	}

	/**
	 * @return callable[]
	 */
	public function getContentHandlerFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'content-handler-factory-callback' );
	}

	/**
	 * @return callable[]
	 */
	public function getEditEntityHandlerFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'edit-entity-handler-factory-callback' );
	}

}

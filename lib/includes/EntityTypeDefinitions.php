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
 * The fields of a definition array can be seen in the follow doc file:
 * @see docs/entitytypes.wiki
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Mättig
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
		foreach ( $entityTypeDefinitions as $type => $def ) {
			if ( !is_string( $type ) || !is_array( $def ) ) {
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
	 * @return array
	 */
	private function getMapForDefinitionField( $field ) {
		$fieldValues = [];

		foreach ( $this->entityTypeDefinitions as $type => $def ) {
			if ( isset( $def[$field] ) ) {
				$fieldValues[$type] = $def[$field];
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
	public function getEntityFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'entity-factory-callback' );
	}

	/**
	 * @return callable[]
	 */
	public function getEntityDifferStrategyBuilders() {
		return $this->getMapForDefinitionField( 'entity-differ-strategy-builder' );
	}

	/**
	 * @return callable[]
	 */
	public function getEntityPatcherStrategyBuilders() {
		return $this->getMapForDefinitionField( 'entity-patcher-strategy-builder' );
	}

	/**
	 * @return string[]
	 */
	public function getJsDeserializerFactoryFunctions() {
		return $this->getMapForDefinitionField( 'js-deserializer-factory-function' );
	}

	/**
	 * @return callable[]
	 */
	public function getEntityIdBuilders() {
		$result = [];

		foreach ( $this->entityTypeDefinitions as $def ) {
			if ( isset( $def['entity-id-pattern'] ) && isset( $def['entity-id-builder'] ) ) {
				$result[$def['entity-id-pattern']] = $def['entity-id-builder'];
			}
		}

		return $result;
	}

	/**
	 * @return callable[] An array mapping entity type identifiers to callables capable of turning
	 *  unique entity ID serialization fragments into EntityId objects. Not guaranteed to contain
	 *  all entity types.
	 */
	public function getEntityIdFragmentBuilders() {
		return $this->getMapForDefinitionField( 'entity-id-fragment-builder' );
	}

}

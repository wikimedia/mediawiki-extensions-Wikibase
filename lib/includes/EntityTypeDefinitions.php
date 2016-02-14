<?php

namespace Wikibase\Lib;

use Wikimedia\Assert\Assert;

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
	 * @var array[]
	 */
	private $entityTypeDefinitions = array();

	/**
	 * @param array[] $entityTypeDefinitions An associative array mapping entity type to entity
	 * definitions. Each entity type definitions are associative arrays, refer to the class level
	 * documentation for details.
	 */
	public function __construct( array $entityTypeDefinitions ) {
		Assert::parameterElementType( 'array', $entityTypeDefinitions, '$entityTypeDefinitions' );
		$this->entityTypeDefinitions = $entityTypeDefinitions;
	}

	/**
	 * @param string $field
	 *
	 * @return array An associative array mapping type IDs to the value of $field given in the
	 * original property data type definition provided to the constructor.
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

}

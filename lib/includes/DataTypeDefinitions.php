<?php

namespace Wikibase\Lib;

use Wikimedia\Assert\Assert;

/**
 * Service that manages data type definition. This is a registry that provides access to
 * factory functions for various services associated with data types, such as validators,
 * parsers, and formatters.
 *
 * DataTypeDefinitions provides a one-stop interface for defining data types. Each data type is defined
 * using a "data type definition" array. A definition array has the following fields:
 * - value-type: the value type used with the data type
 * - validator-factory-callback: a callback for creating validators for the data type,
 *   as used by BuilderBasedDataTypeValidatorFactory.
 * - parser-factory-callback: a callback for instantiating a parser for the data type
 * - formatter-factory-callback: a callback for instantiating a formatter for the data type
 * - rdf-builder-factory-callback: a callback for instantiating a rdf mapping for the data type
 *
 * @see docs/datatypes.wiki
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataTypeDefinitions {

	/**
	 * @var array[]
	 */
	private $dataTypeDefinitions = array();

	/**
	 * @param array[] $dataTypeDefinitions An associative array mapping data type ids to data type
	 * definitions. Data type definitions are associative arrays, refer to the class level
	 * documentation.
	 */
	public function __construct( $dataTypeDefinitions = array() ) {
		Assert::parameterElementType( 'array', $dataTypeDefinitions, '$dataTypeDefinitions' );

		$this->registerDataTypes( $dataTypeDefinitions );
	}

	/**
	 * Adds data type definitions. The new definitions are merged with the existing definitions.
	 * If a data type in $dataTypeDefinitions was already defined, the old definition is not
	 * replaced but the definitions are merged.
	 *
	 * @param array[] $dataTypeDefinitions An associative array mapping data type ids to data type
	 * definitions. Data type definitions are associative arrays, refer to the class level
	 * documentation.
	 */
	public function registerDataTypes( $dataTypeDefinitions ) {
		Assert::parameterElementType( 'array', $dataTypeDefinitions, '$dataTypeDefinitions' );

		foreach ( $dataTypeDefinitions as $id => $def ) {
			if ( isset( $this->dataTypeDefinitions[$id] ) ) {
				$this->dataTypeDefinitions[$id] = array_merge(
					$this->dataTypeDefinitions[$id],
					$dataTypeDefinitions[$id]
				);
			} else {
				$this->dataTypeDefinitions[$id] = $dataTypeDefinitions[$id];
			}
		}
	}

	/**
	 * @return string[]
	 */
	public function getTypeIds() {
		return array_keys( $this->dataTypeDefinitions );
	}

	/**
	 * @param string $field
	 *
	 * @return array An associative array mapping data type IDs to the value of $field
	 * given in the original data type definition provided to the constructor.
	 */
	private function getMapForDefinitionField( $field ) {
		$fieldValues = array();

		foreach ( $this->dataTypeDefinitions as $id => $def ) {
			if ( isset( $def[$field] ) ) {
				$fieldValues[$id] = $def[$field];
			}
		}

		return $fieldValues;
	}

	/**
	 * @return string[]
	 */
	public function getValueTypes() {
		return $this->getMapForDefinitionField( 'value-type' );
	}

	/**
	 * @see BuilderBasedDataTypeValidatorFactory
	 *
	 * @return callable[]
	 */
	public function getValidatorFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'validator-factory-callback' );
	}

	/**
	 * @see ValueParserFactory
	 *
	 * @return callable[]
	 */
	public function getParserFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'parser-factory-callback' );
	}

	/**
	 * @see OutputFormatValueFormatterFactory
	 *
	 * @return callable[]
	 */
	public function getFormatterFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'formatter-factory-callback' );
	}

	/**
	 * @see ValueSnakRdfBuilderFactory
	 *
	 * @return callable[]
	 */
	public function getRdfBuilderFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'rdf-builder-factory-callback' );
	}

}

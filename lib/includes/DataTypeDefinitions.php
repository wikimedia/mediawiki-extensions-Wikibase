<?php

namespace Wikibase\Lib;

use InvalidArgumentException;

/**
 * Service that manages property data type definition. This is a registry that provides access to
 * factory functions for various services associated with property data types, such as validators,
 * parsers, and formatters.
 *
 * DataTypeDefinitions provides a one-stop interface for defining property data types.
 * Each property data type is defined using a "data type definition" array.
 * A definition array has the following fields:
 * - value-type: the value type used with the data type
 * - validator-factory-callback: a callback for creating validators for the data type,
 *   as used by BuilderBasedDataTypeValidatorFactory.
 * - parser-factory-callback: a callback for instantiating a parser for the data type
 * - formatter-factory-callback: a callback for instantiating a formatter for the data type
 * - rdf-builder-factory-callback: a callback for instantiating a rdf mapping for the data type
 *
 * DataTypeDefinitions also supports fallback logic based on the value type associated with each
 * property data type.
 *
 * @see docs/datatypes.wiki
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataTypeDefinitions {

	/**
	 * Constant for indicating that callback maps should be returned with the "VT:" and "PT:"
	 * prefixes in the array keys indicating whether the callback applies to a value type or a
	 * property data type.
	 */
	const PREFIXED_MODE = 'prefixed';

	/**
	 * Constant for indicating that callback maps should be returned for property data types only,
	 * with no prefixes in the array keys, but with fallbacks for value types merged into the
	 * definitions for the property data types.
	 */
	const RESOLVED_MODE = 'resolved';

	/**
	 * @var DefinitionsMap
	 */
	private $dataTypeDefinitions;

	/**
	 * @param array[] $dataTypeDefinitions An associative array mapping property data type ids
	 * (with the prefix "PT:") and value types (with the prefix "VT:") to data type definitions.
	 * Each data type definitions are associative arrays, refer to the class level documentation
	 * for details.
	 * @param string[] $disabledDataTypes Array of disabled data types.
	 */
	public function __construct(
		array $dataTypeDefinitions = array(),
		array $disabledDataTypes = array()
	) {
		$dataTypeDefinitions = $this->filterDisabledDataTypes(
			$dataTypeDefinitions,
			$disabledDataTypes
		);

		foreach ( $dataTypeDefinitions as $id => $def ) {
			if ( 0 !== strpos( $id, 'PT:' ) && 0 !== strpos( $id, 'VT:' ) ) {
				throw new InvalidArgumentException( 'Definition keys must start with either "PT:" or "VT:".' );
			}
		}

		$this->dataTypeDefinitions = new DefinitionsMap( $dataTypeDefinitions );
	}

	/**
	 * @param array[] $dataTypeDefinitions Associative array of data types and definitions.
	 * @param string[] $disabledTypes List of disabled data types
	 *
	 * @return array[] Filtered data type definitions
	 */
	private function filterDisabledDataTypes( array $dataTypeDefinitions, array $disabledTypes ) {
		foreach ( $dataTypeDefinitions as $id => $def ) {
			if ( 0 === strpos( $id, 'PT' ) ) {
				if ( in_array( substr( $id, 3 ), $disabledTypes ) ) {
					unset( $dataTypeDefinitions[$id] );
				}
			}
		}

		return $dataTypeDefinitions;
	}

	/**
	 * @param array $map
	 * @param string $prefix
	 *
	 * @return array A filtered version of $map that only contains the entries
	 *         with keys that match the prefix $prefix, with that prefix removed.
	 */
	private function getFilteredByPrefix( array $map, $prefix ) {
		$filtered = array();
		$offset = strlen( $prefix );

		foreach ( $map as $key => $value ) {
			if ( strpos( $key, $prefix ) === 0 ) {
				$key = substr( $key, $offset );
				$filtered[$key] = $value;
			}
		}

		return $filtered;
	}

	/**
	 * @return string[] a list of all registered property data types.
	 */
	public function getTypeIds() {
		$propertyDataTypeDefinitions = $this->getFilteredByPrefix(
			$this->dataTypeDefinitions->toArray(),
			'PT:'
		);

		return array_keys( $propertyDataTypeDefinitions );
	}

	/**
	 * Resolves value type fallbacks on the given callback map. For each property data type,
	 * the corresponding value type is determined. Then, any data type missing from $callbackMap
	 * is filled in with the value for the corrsponding value type. The resulting array will
	 * have no PT or VT prefixes.
	 *
	 * @param array $callbackMap The callback map to process.
	 *
	 * @return array An associative array mapping data type IDs to the value of $field
	 * given in the original property data type definition provided to the constructor.
	 * The keys in this array are plain property data type IDs without a prefix.
	 */
	private function resolveValueTypeFallback( array $callbackMap ) {
		$resolved = array();

		foreach ( $this->getValueTypes() as $propertyType => $valueType ) {
			$vtKey = "VT:$valueType";
			$ptKey = "PT:$propertyType";

			if ( isset( $callbackMap[$ptKey] ) ) {
				$resolved[$propertyType] = $callbackMap[$ptKey];
			} elseif ( isset( $callbackMap[$vtKey] ) ) {
				$resolved[$propertyType] = $callbackMap[$vtKey];
			}
		}

		return $resolved;
	}

	/**
	 * Applies the given mode to the $callbackMap. If $mode is PREFIXED_MODE, $callbackMap is
	 * returned unchanged. If $mode is RESOLVED_MODE, resolveValueTypeFallback() is applied
	 * to $callbackMap. The resulting map will have no prefixes in the array keys, and will
	 * contain entries for all property data types, with value type fallback applied.
	 *
	 * @param array $callbackMap
	 * @param string $mode PREFIXED_MODE or RESOLVED_MODE
	 *
	 * @return array A version of $callbackMap with $mode applied.
	 */
	private function applyMode( array $callbackMap, $mode ) {
		if ( $mode === self::RESOLVED_MODE ) {
			return $this->resolveValueTypeFallback( $callbackMap );
		} else {
			return $callbackMap;
		}
	}

	/**
	 * @return string[] An associative array mapping property data types to value types.
	 */
	public function getValueTypes() {
		return $this->getFilteredByPrefix(
			$this->dataTypeDefinitions->getMapForDefinitionField( 'value-type' ),
			'PT:'
		);
	}

	/**
	 * @see BuilderBasedDataTypeValidatorFactory
	 *
	 * @param string $mode PREFIXED_MODE to request a callback map with "VT:" and "PT:" prefixes
	 * for value types and property data types, or RESOLVED_MODE to retrieve a callback map for
	 * property data types only, with value type fallback applied.
	 *
	 * @return callable[]
	 */
	public function getValidatorFactoryCallbacks( $mode = self::RESOLVED_MODE ) {
		return $this->applyMode(
			$this->dataTypeDefinitions->getMapForDefinitionField( 'validator-factory-callback' ),
			$mode
		);
	}

	/**
	 * @see ValueParserFactory
	 *
	 * @param string $mode PREFIXED_MODE to request a callback map with "VT:" and "PT:" prefixes
	 * for value types and property data types, or RESOLVED_MODE to retrieve a callback map for
	 * property data types only, with value type fallback applied.
	 *
	 * @return callable[]
	 */
	public function getParserFactoryCallbacks( $mode = self::RESOLVED_MODE ) {
		return $this->applyMode(
			$this->dataTypeDefinitions->getMapForDefinitionField( 'parser-factory-callback' ),
			$mode
		);
	}

	/**
	 * @see OutputFormatValueFormatterFactory
	 *
	 * @param string $mode PREFIXED_MODE to request a callback map with "VT:" and "PT:" prefixes
	 * for value types and property data types, or RESOLVED_MODE to retrieve a callback map for
	 * property data types only, with value type fallback applied.
	 *
	 * @return callable[]
	 */
	public function getFormatterFactoryCallbacks( $mode = self::RESOLVED_MODE ) {
		return $this->applyMode(
			$this->dataTypeDefinitions->getMapForDefinitionField( 'formatter-factory-callback' ),
			$mode
		);
	}

	/**
	 * @see OutputFormatSnakFormatterFactory
	 *
	 * @return callable[]
	 */
	public function getSnakFormatterFactoryCallbacks() {
		return $this->dataTypeDefinitions->getMapForDefinitionField( 'snak-formatter-factory-callback' );
	}

	/**
	 * @see ValueSnakRdfBuilderFactory
	 *
	 * @param string $mode PREFIXED_MODE to request a callback map with "VT:" and "PT:" prefixes
	 * for value types and property data types, or RESOLVED_MODE to retrieve a callback map for
	 * property data types only, with value type fallback applied.
	 *
	 * @return callable[]
	 */
	public function getRdfBuilderFactoryCallbacks( $mode = self::RESOLVED_MODE ) {
		return $this->applyMode(
			$this->dataTypeDefinitions->getMapForDefinitionField( 'rdf-builder-factory-callback' ),
			$mode
		);
	}

}

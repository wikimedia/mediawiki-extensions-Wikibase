<?php

namespace Wikibase\Lib;

use UnexpectedValueException;
use Wikimedia\Assert\Assert;

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
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DataTypeDefinitions {

	/**
	 * Constant for indicating that callback maps should be returned with the "VT:" and "PT:"
	 * prefixes in the array keys indicating whether the callback applies to a value type or a
	 * property data type.
	 */
	public const PREFIXED_MODE = 'prefixed';

	/**
	 * Constant for indicating that callback maps should be returned for property data types only,
	 * with no prefixes in the array keys, but with fallbacks for value types merged into the
	 * definitions for the property data types.
	 */
	private const RESOLVED_MODE = 'resolved';

	/**
	 * @var array[]
	 */
	private $dataTypeDefinitions = [];

	/**
	 * @param array[] $dataTypeDefinitions An associative array mapping property data type ids
	 * (with the prefix "PT:") and value types (with the prefix "VT:") to data type definitions.
	 * Each data type definitions are associative arrays, refer to the class level documentation
	 * for details.
	 * @param string[] $disabledDataTypes Array of disabled data types.
	 */
	public function __construct( array $dataTypeDefinitions, array $disabledDataTypes = [] ) {
		$dataTypeDefinitions = $this->filterDisabledDataTypes(
			$dataTypeDefinitions,
			$disabledDataTypes
		);

		$this->registerDataTypes( $dataTypeDefinitions );
	}

	/**
	 * Adds data type definitions. The new definitions are merged with the existing definitions.
	 * If a data type in $dataTypeDefinitions was already defined, the old definition is not
	 * replaced but the definitions are merged.
	 *
	 * @param array[] $dataTypeDefinitions An associative array mapping property data type ids
	 * (with the prefix "PT:") and value types (with the prefix "VT:") to data type definitions.
	 * Each data type definitions are associative arrays, refer to the class level documentation
	 * for details.
	 */
	public function registerDataTypes( array $dataTypeDefinitions ) {
		Assert::parameterElementType( 'array', $dataTypeDefinitions, '$dataTypeDefinitions' );

		foreach ( $dataTypeDefinitions as $id => $def ) {
			Assert::parameter(
				strpos( $id, ':' ),
				"\$dataTypeDefinitions[$id]",
				'Key must start with a prefix like "PT:" or "VT:".'
			);

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
	 * @param array[] $dataTypeDefinitions Associative array of data types and definitions.
	 * @param string[] $disabledTypes List of disabled data types
	 *
	 * @return array[] Filtered data type definitions
	 */
	private function filterDisabledDataTypes( array $dataTypeDefinitions, array $disabledTypes ) {
		foreach ( $dataTypeDefinitions as $id => $def ) {
			if ( strpos( $id, 'PT' ) === 0 ) {
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
		$filtered = [];

		foreach ( $map as $key => $value ) {
			$ofs = strlen( $prefix );
			if ( strpos( $key, $prefix ) === 0 ) {
				$key = substr( $key, $ofs );
				$filtered[$key] = $value;
			}
		}

		return $filtered;
	}

	/**
	 * @return string[] a list of all registered property data types.
	 */
	public function getTypeIds() {
		$ptDefinitions = $this->getFilteredByPrefix( $this->dataTypeDefinitions, 'PT:' );
		return array_keys( $ptDefinitions );
	}

	public function getExpertModules() {
		return $this->resolveValueTypeFallback(
			$this->getMapForDefinitionField( 'expert-module' )
		);
	}

	/**
	 * @param string $field
	 *
	 * @return array An associative array mapping type IDs (with "VT:" or "PT:" prefixes) to the
	 * value of $field given in the original property data type definition provided to the
	 * constructor.
	 */
	private function getMapForDefinitionField( $field ) {
		$fieldValues = [];

		foreach ( $this->dataTypeDefinitions as $id => $def ) {
			if ( isset( $def[$field] ) ) {
				$fieldValues[$id] = $def[$field];
			}
		}

		return $fieldValues;
	}

	/**
	 * Resolves value type fallbacks on the given definition map. For each property data type,
	 * the corresponding value type is determined. Then, any data type missing from $definitions
	 * is filled in with the value for the corresponding value type. The resulting array will
	 * have no PT or VT prefixes.
	 *
	 * @param array $definitions The map to process.
	 *
	 * @throws UnexpectedValueException
	 * @return array An associative array mapping data type IDs to one of the $definitions values.
	 * The keys in this array are plain property data type IDs without a prefix.
	 */
	private function resolveValueTypeFallback( array $definitions ) {
		$resolved = [];

		foreach ( $this->getValueTypes() as $propertyType => $valueType ) {
			$ptKey = "PT:$propertyType";
			$vtKey = "VT:$valueType";

			if ( !empty( $definitions[$ptKey] ) ) {
				$resolved[$propertyType] = $definitions[$ptKey];
			} elseif ( !empty( $definitions[$vtKey] ) ) {
				$resolved[$propertyType] = $definitions[$vtKey];
			} else {
				throw new UnexpectedValueException( "Missing definition for $ptKey or $vtKey" );
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
			$this->getMapForDefinitionField( 'value-type' ),
			'PT:'
		);
	}

	/**
	 * @return string[] An associative array mapping some property data types to types URIs for
	 *         use in RDF. This does not have to cover all known property data types. For those
	 *         that od not explicitly define a URI, RdfVocabulary will generate one.
	 *         Note that property data type URIs are not intended to be used as RDF literal types.
	 */
	public function getRdfTypeUris() {
		return $this->getFilteredByPrefix(
			$this->getMapForDefinitionField( 'rdf-uri' ),
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
			$this->getMapForDefinitionField( 'validator-factory-callback' ),
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
			$this->getMapForDefinitionField( 'parser-factory-callback' ),
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
			$this->getMapForDefinitionField( 'formatter-factory-callback' ),
			$mode
		);
	}

	/**
	 * @see OutputFormatSnakFormatterFactory
	 *
	 * @return callable[]
	 */
	public function getSnakFormatterFactoryCallbacks() {
		return $this->getMapForDefinitionField( 'snak-formatter-factory-callback' );
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
			$this->getMapForDefinitionField( 'rdf-builder-factory-callback' ),
			$mode
		);
	}

	/**
	 * Get data formatters for search indexing for each type.
	 * @return callable[] List of callbacks, with keys having "VT:" prefixes.
	 */
	public function getSearchIndexDataFormatterCallbacks() {
		return $this->getMapForDefinitionField( 'search-index-data-formatter-callback' );
	}

	/**
	 * Produce array of types for RDF.
	 * Using PropertyRdfBuilder constants for data types is recommended.
	 * In situations where PropertyRdfBuilder has not been autoloaded yet,
	 * the type may be wrapped in a callback.
	 * @return string[]
	 */
	public function getRdfDataTypes() {
		return array_map(
			function ( $dataType ) {
				if ( is_callable( $dataType ) ) {
					$dataType = ( $dataType )();
				}
				return $dataType;
			},
			$this->getFilteredByPrefix(
				$this->getMapForDefinitionField( 'rdf-data-type' ),
				'PT:'
			)
		);
	}

	/**
	 * Get {@link DataValueNormalizer data value normalizers} for each type.
	 * @return callable[] List of callbacks, with keys having "VT:" and "PT:" prefixes.
	 */
	public function getNormalizerFactoryCallbacks(): array {
		return $this->getMapForDefinitionField( 'normalizer-factory-callback' );
	}

}

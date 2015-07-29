<?php

namespace Wikibase\Lib\Serialization;

use ApiResult;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @since 0.5
 * @author Adam Shorland
 */
class CallbackFactory {

	/**
	 * Get callable to index array with the given tag name
	 *
	 * @param string $tagName
	 *
	 * @return callable
	 */
	public function getCallbackToIndexTags( $tagName ) {
		return function( $array ) use ( $tagName ) {
			if ( is_array( $array ) ) {
				ApiResult::setIndexedTagName( $array, $tagName );
			}
			return $array;
		};
	}

	/**
	 * Get callable to set the keys in an array as values
	 *
	 * @param string $elementKey
	 *
	 * @return callable
	 */
	public function getCallbackToAddKeysAsElement( $elementKey ) {
		return function ( $array ) use ( $elementKey ) {
			foreach ( $array as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value[$elementKey] = $key;
				}
			}
			return $array;
		};
	}

	public function getCallbackToAddDataTypeToSnaksGroupedByProperty(
		PropertyDataTypeLookup $dataTypeLookup
	) {
		return function ( $array ) use ( $dataTypeLookup ) {
			foreach ( $array as $propertyIdGroupKey => &$snakGroup ) {
				$dataType = $dataTypeLookup->getDataTypeIdForProperty( new PropertyId( $propertyIdGroupKey ) );
				foreach ( $snakGroup as &$snak ) {
					$snak['datatype'] = $dataType;
				}
			}
			return $array;
		};
	}

	public function getCallbackToAddDataTypeToSnak( PropertyDataTypeLookup $dataTypeLookup ) {
		return function ( $array ) use ( $dataTypeLookup ) {
			$dataType = $dataTypeLookup->getDataTypeIdForProperty( new PropertyId( $array['property'] ) );
			$array['datatype'] = $dataType;
			return $array;
		};
	}

}

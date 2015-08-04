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
	 * Get callable to remove array keys and optionally set the key as an array value
	 *
	 * @param string|null $addAsArrayElement
	 *
	 * @return callable
	 */
	public function getCallbackToRemoveKeys( $addAsArrayElement = null ) {
		return function ( $array ) use ( $addAsArrayElement ) {
			if ( $addAsArrayElement !== null ) {
				foreach ( $array as $key => &$value ) {
					$value[$addAsArrayElement] = $key;
				}
			}
			$array = array_values( $array );
			return $array;
		};
	}

	public function getCallbackToAddDataTypeToSnaksGroupedByProperty(
		PropertyDataTypeLookup $dataTypeLookup
	) {
		return function ( $value ) use ( $dataTypeLookup ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $propertyIdGroupKey => &$snakGroup ) {
					$dataType = $dataTypeLookup->getDataTypeIdForProperty( new PropertyId( $propertyIdGroupKey ) );
					foreach ( $snakGroup as &$snak ) {
						$snak['datatype'] = $dataType;
					}
				}
			} else {
				foreach ( get_object_vars( $value ) as $propertyIdGroupKey => $snakGroup ) {
					$dataType = $dataTypeLookup->getDataTypeIdForProperty( new PropertyId( $propertyIdGroupKey ) );
					foreach ( get_object_vars( $snakGroup ) as $snakKey => $snak ) {
						$value->$propertyIdGroupKey->$snakKey->datatype = $dataType;
					}
				}
			}
			return $value;
		};
	}

	public function getCallbackToAddDataTypeToSnak( PropertyDataTypeLookup $dataTypeLookup ) {
		return function ( $value ) use ( $dataTypeLookup ) {
			if ( is_array( $value ) ) {
				$dataType = $dataTypeLookup->getDataTypeIdForProperty( new PropertyId( $value['property'] ) );
				$value['datatype'] = $dataType;
			} else {
				if ( !isset( $value->property ) ) {
					return $value;
				}
				$dataType = $dataTypeLookup->getDataTypeIdForProperty( new PropertyId( $value->property ) );
				$value->datatype = $dataType;
			}

			return $value;
		};
	}

}

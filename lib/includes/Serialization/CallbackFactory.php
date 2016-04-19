<?php

namespace Wikibase\Lib\Serialization;

use ApiResult;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;

/**
 * @since 0.5
 * @author Addshore
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
	 * Get callable to index array with the given tag name
	 *
	 * @param string $type
	 * @param string|null $kvpKeyName
	 *
	 * @return callable
	 */
	public function getCallbackToSetArrayType( $type, $kvpKeyName = null ) {
		return function( $array ) use ( $type, $kvpKeyName ) {
			if ( is_array( $array ) ) {
				ApiResult::setArrayType( $array, $type, $kvpKeyName );
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
		return function ( $array ) use ( $dataTypeLookup ) {
			foreach ( $array as $propertyIdGroupKey => &$snakGroup ) {
				try {
					$dataType = $dataTypeLookup->getDataTypeIdForProperty( new PropertyId( $propertyIdGroupKey ) );
					foreach ( $snakGroup as &$snak ) {
						$snak['datatype'] = $dataType;
					}
				} catch ( PropertyDataTypeLookupException $e ) {
					//XXX: shall we set $serialization['datatype'] = 'bad' ??
				}
			}
			return $array;
		};
	}

	public function getCallbackToAddDataTypeToSnak( PropertyDataTypeLookup $dataTypeLookup ) {
		return function ( $array ) use ( $dataTypeLookup ) {
			if ( is_array( $array ) ) {
				try {
					$dataType = $dataTypeLookup->getDataTypeIdForProperty( new PropertyId( $array['property'] ) );
					$array['datatype'] = $dataType;
				} catch ( PropertyDataTypeLookupException $e ) {
					//XXX: shall we set $serialization['datatype'] = 'bad' ??
				}
			}
			return $array;
		};
	}

}

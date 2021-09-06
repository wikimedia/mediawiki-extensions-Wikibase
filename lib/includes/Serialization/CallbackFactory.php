<?php

namespace Wikibase\Lib\Serialization;

use ApiResult;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
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

	public function getCallbackToAddDataTypeToSnaksGroupedByProperty(
		PropertyDataTypeLookup $dataTypeLookup,
		EntityIdParser $entityIdParser
	) {
		return function ( $array ) use ( $dataTypeLookup, $entityIdParser ) {
			if ( !is_array( $array ) ) {
				return $array;
			}
			foreach ( $array as $propertyIdGroupKey => &$snakGroup ) {
				try {
					$dataType = $dataTypeLookup->getDataTypeIdForProperty( $this->getPropertyFromSerialization(
						$entityIdParser,
						$propertyIdGroupKey
					) );
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

	public function getCallbackToAddDataTypeToSnak( PropertyDataTypeLookup $dataTypeLookup, EntityIdParser $entityIdParser ) {
		return function ( $array ) use ( $dataTypeLookup, $entityIdParser ) {
			if ( is_array( $array ) ) {
				try {
					$array['datatype'] = $dataTypeLookup->getDataTypeIdForProperty( $this->getPropertyFromSerialization(
						$entityIdParser,
						$array['property']
					) );
				} catch ( PropertyDataTypeLookupException $e ) {
					//XXX: shall we set $serialization['datatype'] = 'bad' ??
				}
			}
			return $array;
		};
	}

	private function getPropertyFromSerialization( EntityIdParser $parser, string $id ): PropertyId {
		$propertyId = $parser->parse( $id );

		Assert::postcondition( $propertyId instanceof PropertyId, '$id must be a valid PropertyId serialization' );
		/** @var PropertyId $propertyId */
		'@phan-var PropertyId $propertyId';

		return $propertyId;
	}

}

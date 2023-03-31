<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use DataValues\DataValue;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyValuePair;
use Wikibase\Repo\RestApi\Domain\ReadModel\Value;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePairSerializer {

	private PropertyDataTypeLookup $dataTypeLookup;

	public function __construct( PropertyDataTypeLookup $dataTypeLookup ) {
		$this->dataTypeLookup = $dataTypeLookup;
	}

	public function serialize( PropertyValuePair $propertyValuePair ): array {
		$serialization = [
			'property' => [
				'id' => $propertyValuePair->getProperty()->getId()->getSerialization(),
				'data-type' => $propertyValuePair->getProperty()->getDataType(),
			],
			'value' => [
				'type' => $propertyValuePair->getValue()->getType(),
			],
		];

		if ( $propertyValuePair->getValue()->getType() === Value::TYPE_VALUE ) {
			$serialization['value']['content'] = $this->serializeValueContent( $propertyValuePair->getValue()->getContent() );
		}

		return $serialization;
	}

	public function serializeSnak( Snak $snak ): array {
		$propertyId = $snak->getPropertyId();

		try {
			$propertyDataType = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( PropertyDataTypeLookupException $exception ) {
			$propertyDataType = null;
		}

		$propertyValuePair = [
			'property' => [
				'id' => $propertyId->getSerialization(),
				'data-type' => $propertyDataType,
			],
			'value' => [
				'type' => $snak->getType(),
			],
		];

		if ( $snak instanceof PropertyValueSnak ) {
			$propertyValuePair['value']['content'] = $this->serializeValueContent( $snak->getDataValue() );
		}

		return $propertyValuePair;
	}

	/**
	 * @return mixed
	 */
	private function serializeValueContent( DataValue $value ) {
		$content = $value->getArrayValue();
		switch ( $value->getType() ) {
			case 'wikibase-entityid':
				return $content['id'];
			case 'time':
				foreach ( [ 'before', 'after', 'timezone' ] as $key ) {
					unset( $content[$key] );
				}
				break;
			case 'globecoordinate':
				unset( $content['altitude'] );
				break;
		}

		return $content;
	}

}

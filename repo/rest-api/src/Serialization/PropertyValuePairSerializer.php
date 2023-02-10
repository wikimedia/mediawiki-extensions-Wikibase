<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePairSerializer {

	private PropertyDataTypeLookup $dataTypeLookup;

	public function __construct( PropertyDataTypeLookup $dataTypeLookup ) {
		$this->dataTypeLookup = $dataTypeLookup;
	}

	public function serialize( Snak $snak ): array {
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
			$content = $snak->getDataValue()->getArrayValue();
			switch ( $snak->getDataValue()->getType() ) {
				case 'wikibase-entityid':
					$content = $content['id'];
					break;
				case 'time':
					foreach ( [ 'before', 'after', 'timezone' ] as $key ) {
						unset( $content[$key] );
					}
					break;
				case 'globecoordinate':
					unset( $content['altitude'] );
					break;
			}
			$propertyValuePair['value']['content'] = $content;
		}

		return $propertyValuePair;
	}

}

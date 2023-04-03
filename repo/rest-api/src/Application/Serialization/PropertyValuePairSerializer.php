<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use DataValues\DataValue;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyValuePair;
use Wikibase\Repo\RestApi\Domain\ReadModel\Value;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePairSerializer {

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

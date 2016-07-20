<?php

namespace Wikibase\Repo;

use DataValues\DataValue;
use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class MinifyingDataValueSerializer implements DispatchableSerializer {

	/**
	 * @see Serializer::serialize
	 *
	 * @param DataValue $dataValue
	 *
	 * @throws UnsupportedObjectException
	 * @return array
	 */
	public function serialize( $dataValue ) {
		if ( !( $dataValue instanceof DataValue ) ) {
			throw new UnsupportedObjectException( $dataValue, '$dataValue must be a DataValue' );
		}

		$serialization = $dataValue->toArray();

		if ( $dataValue instanceof EntityIdValue && isset( $serialization['value']['id'] ) ) {
			unset( $serialization['value']['entity-type'] );
			unset( $serialization['value']['numeric-id'] );
		}

		return $serialization;
	}

	/**
	 * @see DispatchableSerializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof DataValue;
	}

}

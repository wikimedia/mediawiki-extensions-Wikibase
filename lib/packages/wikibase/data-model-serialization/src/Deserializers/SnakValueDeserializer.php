<?php

namespace Wikibase\DataModel\Deserializers;

use DataValues\DataValue;
use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Exceptions\DeserializationException;
use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 */
class SnakValueDeserializer {

	private array $deserializerBuilders;
	private DataValueDeserializer $dataValueDeserializer;

	public function __construct( DataValueDeserializer $dataValueDeserializer, array $deserializerBuilders ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->deserializerBuilders = $deserializerBuilders;
	}

	/**
	 * @throws DeserializationException
	 */
	public function deserialize( string $dataType, array $serialization ): DataValue {
		$builder = $this->deserializerBuilders["PT:$dataType"] ?? null;
		if ( !$builder ) {
			return $this->dataValueDeserializer->deserialize( $serialization );
		}

		try {
			if ( is_callable( $builder ) ) {
				return $builder( $serialization[DataValueDeserializer::VALUE_KEY] );
			}

			/** @var DataValue $builder */
			return $builder::newFromArray( $serialization[DataValueDeserializer::VALUE_KEY] );
		} catch ( InvalidArgumentException $ex ) {
			throw new DeserializationException( $ex->getMessage(), $ex );
		}
	}

}

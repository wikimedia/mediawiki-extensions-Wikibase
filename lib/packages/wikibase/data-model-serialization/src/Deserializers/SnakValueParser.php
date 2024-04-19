<?php

namespace Wikibase\DataModel\Deserializers;

use DataValues\DataValue;
use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Exceptions\DeserializationException;

/**
 * @license GPL-2.0-or-later
 */
class SnakValueParser {

	private DataValueDeserializer $dataValueDeserializer;

	private array $valueParserCallbacks;

	public function __construct( DataValueDeserializer $dataValueDeserializer, array $valueParserCallbacks ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->valueParserCallbacks = $valueParserCallbacks;
	}

	/**
	 * @throws DeserializationException
	 */
	public function parse( string $dataType, array $serialization ): DataValue {
		return isset( $this->valueParserCallbacks["PT:$dataType"] )
			? $this->valueParserCallbacks["PT:$dataType"]()->parse( $serialization )
			: $this->dataValueDeserializer->deserialize( $serialization );
	}

}

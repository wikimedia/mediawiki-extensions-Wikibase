<?php

namespace Wikibase\InternalSerialization;

use Deserializers\Deserializer;
use Wikibase\DataModel\DeserializerFactory as CurrentDeserializerFactory;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * @since 1.0
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DeserializerFactory {

	private $dataValueDeserializer;
	private $idParser;

	/**
	 * @var LegacyDeserializerFactory
	 */
	private $legacyFactory;

	/**
	 * @var CurrentDeserializerFactory
	 */
	private $currentFactory;

	public function __construct( Deserializer $dataValueDeserializer, EntityIdParser $idParser ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->idParser = $idParser;

		$this->legacyFactory = new LegacyDeserializerFactory( $dataValueDeserializer, $idParser );
		$this->currentFactory = new CurrentDeserializerFactory( $dataValueDeserializer, $idParser );
	}

	/**
	 * @return Deserializer
	 */
	public function newEntityDeserializer() {
		// TODO
	}

	/**
	 * @return Deserializer
	 */
	public function newSnakDeserializer() {
		// TODO
	}

}
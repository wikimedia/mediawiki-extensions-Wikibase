<?php

namespace Wikibase\DataModel;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\ReferenceDeserializer;
use Wikibase\DataModel\Deserializers\ReferencesDeserializer;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Deserializers\SnaksDeserializer;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Factory for constructing Deserializer objects that can deserialize WikibaseDataModel objects.
 *
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class DeserializerFactory {

	/**
	 * @var Deserializer
	 */
	protected $dataValueDeserializer;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param Deserializer $dataValueDeserializer deserializer for DataValue objects
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( Deserializer $dataValueDeserializer, EntityIdParser $entityIdParser ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * Returns a Deserializer that can deserialize References objects.
	 *
	 * @return Deserializer
	 */
	public function newReferencesDeserializer() {
		return new ReferencesDeserializer( $this->newReferenceDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Reference objects.
	 *
	 * @return Deserializer
	 */
	public function newReferenceDeserializer() {
		return new ReferenceDeserializer( $this->newSnaksDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Snaks objects.
	 *
	 * @return Deserializer
	 */
	public function newSnaksDeserializer() {
		return new SnaksDeserializer( $this->newSnakDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Snak objects.
	 *
	 * @return Deserializer
	 */
	public function newSnakDeserializer() {
		return new SnakDeserializer( $this->dataValueDeserializer, $this->entityIdParser );
	}
}
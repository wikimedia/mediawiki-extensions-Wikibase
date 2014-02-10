<?php

namespace Wikibase\DataModel;

use Serializers\Serializer;
use Wikibase\DataModel\Serializers\ClaimSerializer;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Serializers\SnaksSerializer;

/**
 * Factory for constructing Serializer objects that can serialize WikibaseDataModel objects.
 *
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SerializerFactory {

	/**
	 * @var Serializer
	 */
	protected $dataValueSerializer;

	/**
	 * @param Serializer $dataValueSerializer serializer for DataValue objects
	 */
	public function __construct( Serializer $dataValueSerializer ) {
		$this->dataValueSerializer = $dataValueSerializer;
	}

	/**
	 * Returns a Serializer that can serialize Claim objects.
	 *
	 * @return Serializer
	 */
	public function newClaimSerializer() {
		return new ClaimSerializer( $this->newSnakSerializer(), $this->newSnaksSerializer() );
	}

	/**
	 * Returns a Serializer that can serialize Reference objects.
	 *
	 * @return Serializer
	 */
	public function newReferenceSerializer() {
		return new ReferenceSerializer( $this->newSnaksSerializer() );
	}

	/**
	 * Returns a Serializer that can serialize Snaks objects.
	 *
	 * @return Serializer
	 */
	public function newSnaksSerializer() {
		return new SnaksSerializer( $this->newSnakSerializer() );
	}

	/**
	 * Returns a Serializer that can serialize Snak objects.
	 *
	 * @return Serializer
	 */
	public function newSnakSerializer() {
		return new SnakSerializer( $this->dataValueSerializer );
	}
}
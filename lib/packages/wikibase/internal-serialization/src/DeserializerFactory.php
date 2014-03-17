<?php

namespace Wikibase\InternalSerialization;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\InternalSerialization\Deserializers\ClaimDeserializer;
use Wikibase\InternalSerialization\Deserializers\EntityIdDeserializer;
use Wikibase\InternalSerialization\Deserializers\ItemDeserializer;
use Wikibase\InternalSerialization\Deserializers\PropertyDeserializer;
use Wikibase\InternalSerialization\Deserializers\SiteLinkListDeserializer;
use Wikibase\InternalSerialization\Deserializers\SnakDeserializer;
use Wikibase\InternalSerialization\Deserializers\SnakListDeserializer;
use Wikibase\InternalSerialization\Deserializers\TermsDeserializer;

/**
 * @since 1.0
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DeserializerFactory {

	private $dataValueDeserializer;
	private $idParser;

	public function __construct( Deserializer $dataValueDeserializer, EntityIdParser $idParser ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->idParser = $idParser;
	}

	/**
	 * @return Deserializer
	 */
	public function newItemDeserializer() {
		return new ItemDeserializer(
			$this->newEntityIdDeserializer(),
			$this->newSiteLinkListDeserializer(),
			$this->newClaimDeserializer(),
			$this->newTermsDeserializer()
		);
	}

	/**
	 * @return Deserializer
	 */
	public function newPropertyDeserializer() {
		return new PropertyDeserializer(
			$this->newEntityIdDeserializer(),
			$this->newTermsDeserializer()
		);
	}

	/**
	 * @return Deserializer
	 */
	public function newEntityIdDeserializer() {
		return new EntityIdDeserializer( $this->idParser );
	}

	/**
	 * @return Deserializer
	 */
	public function newTermsDeserializer() {
		return new TermsDeserializer();
	}

	/**
	 * @return Deserializer
	 */
	public function newSiteLinkListDeserializer() {
		return new SiteLinkListDeserializer();
	}

	/**
	 * @return Deserializer
	 */
	public function newClaimDeserializer() {
		$snakDeserializer = $this->newSnakDeserializer();

		return new ClaimDeserializer(
			$snakDeserializer,
			new SnakListDeserializer( $snakDeserializer )
		);
	}

	/**
	 * @return Deserializer
	 */
	public function newSnakListDeserializer() {
		return new SnakListDeserializer( $this->newSnakDeserializer() );
	}

	/**
	 * @return Deserializer
	 */
	public function newSnakDeserializer() {
		return new SnakDeserializer( $this->dataValueDeserializer );
	}

}
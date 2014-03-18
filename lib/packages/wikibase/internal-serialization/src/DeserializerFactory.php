<?php

namespace Wikibase\InternalSerialization;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\InternalSerialization\Deserializers\LegacyClaimDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityIdDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyItemDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyPropertyDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySiteLinkListDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakListDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyTermsDeserializer;

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
		return new LegacyItemDeserializer(
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
		return new LegacyPropertyDeserializer(
			$this->newEntityIdDeserializer(),
			$this->newTermsDeserializer()
		);
	}

	/**
	 * @return Deserializer
	 */
	public function newEntityDeserializer() {
		return new LegacyEntityDeserializer(
			$this->newItemDeserializer(),
			$this->newPropertyDeserializer()
		);
	}

	/**
	 * @return Deserializer
	 */
	public function newEntityIdDeserializer() {
		return new LegacyEntityIdDeserializer( $this->idParser );
	}

	/**
	 * @return Deserializer
	 */
	public function newTermsDeserializer() {
		return new LegacyTermsDeserializer();
	}

	/**
	 * @return Deserializer
	 */
	public function newSiteLinkListDeserializer() {
		return new LegacySiteLinkListDeserializer();
	}

	/**
	 * @return Deserializer
	 */
	public function newClaimDeserializer() {
		$snakDeserializer = $this->newSnakDeserializer();

		return new LegacyClaimDeserializer(
			$snakDeserializer,
			new LegacySnakListDeserializer( $snakDeserializer )
		);
	}

	/**
	 * @return Deserializer
	 */
	public function newSnakListDeserializer() {
		return new LegacySnakListDeserializer( $this->newSnakDeserializer() );
	}

	/**
	 * @return Deserializer
	 */
	public function newSnakDeserializer() {
		return new LegacySnakDeserializer( $this->dataValueDeserializer );
	}

}
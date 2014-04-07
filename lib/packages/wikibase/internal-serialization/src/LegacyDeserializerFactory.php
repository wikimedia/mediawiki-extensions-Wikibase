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
use Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer;

/**
 * This factory is package private. Outside access is prohibited.
 *
 * Factory for constructing deserializers that implement handling for the legacy format.
 *
 * @since 1.0
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyDeserializerFactory {

	private $dataValueDeserializer;
	private $idParser;

	public function __construct( Deserializer $dataValueDeserializer, EntityIdParser $idParser ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->idParser = $idParser;
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
	private function newItemDeserializer() {
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
	private function newPropertyDeserializer() {
		return new LegacyPropertyDeserializer(
			$this->newEntityIdDeserializer(),
			$this->newTermsDeserializer()
		);
	}

	/**
	 * @return Deserializer
	 */
	private function newEntityIdDeserializer() {
		return new LegacyEntityIdDeserializer( $this->idParser );
	}

	/**
	 * @return Deserializer
	 */
	private function newTermsDeserializer() {
		return new LegacyFingerprintDeserializer();
	}

	/**
	 * @return Deserializer
	 */
	private function newSiteLinkListDeserializer() {
		return new LegacySiteLinkListDeserializer();
	}

	/**
	 * @return Deserializer
	 */
	private function newClaimDeserializer() {
		$snakDeserializer = $this->newSnakDeserializer();

		return new LegacyClaimDeserializer(
			$snakDeserializer,
			new LegacySnakListDeserializer( $snakDeserializer )
		);
	}

	/**
	 * @return Deserializer
	 */
	public function newSnakDeserializer() {
		return new LegacySnakDeserializer( $this->dataValueDeserializer );
	}

}
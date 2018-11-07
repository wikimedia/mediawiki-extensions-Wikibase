<?php

namespace Wikibase\InternalSerialization;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityIdDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyItemDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyPropertyDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySiteLinkListDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakListDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyStatementDeserializer;

/**
 * Factory for constructing deserializers that implement handling for the legacy format.
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyDeserializerFactory {

	/**
	 * @var Deserializer
	 */
	private $dataValueDeserializer;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	public function __construct( Deserializer $dataValueDeserializer, EntityIdParser $idParser ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->idParser = $idParser;
	}

	/**
	 * @return DispatchableDeserializer
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
			$this->newStatementDeserializer(),
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
	 * @deprecated since 1.4 - use newStatementDeserializer instead
	 *
	 * @return Deserializer
	 */
	public function newClaimDeserializer() {
		return $this->newStatementDeserializer();
	}

	/**
	 * @return DispatchableDeserializer
	 */
	public function newStatementDeserializer() {
		return new LegacyStatementDeserializer(
			$this->newSnakDeserializer(),
			$this->newSnakListDeserializer()
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

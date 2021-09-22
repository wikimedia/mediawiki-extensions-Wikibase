<?php

namespace Wikibase\InternalSerialization;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Wikibase\DataModel\Deserializers\DeserializerFactory as CurrentDeserializerFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\InternalSerialization\Deserializers\EntityDeserializer;
use Wikibase\InternalSerialization\Deserializers\StatementDeserializer;

/**
 * Public interface of the library for constructing deserializers.
 * Direct access to deserializers is prohibited, users are only allowed to
 * know about this interface. Also note that the return type of the methods
 * is "Deserializer". You are also not allowed to know which concrete
 * implementation is returned.
 *
 * The returned deserializers can handle both serializations in the
 * legacy internal format and in the new one.
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DeserializerFactory {

	/**
	 * @var LegacyDeserializerFactory
	 */
	private $legacyFactory;

	/**
	 * @var CurrentDeserializerFactory
	 */
	private $currentFactory;

	/**
	 * @var DispatchableDeserializer|null
	 */
	private $currentEntityDeserializer;

	/**
	 * @param Deserializer $dataValueDeserializer
	 * @param EntityIdParser $idParser
	 * @param DispatchableDeserializer|null $currentEntityDeserializer used instead of constructing
	 *        a new current Deserializer for entities using a current DeserializerFactory.
	 */
	public function __construct(
		Deserializer $dataValueDeserializer,
		EntityIdParser $idParser,
		DispatchableDeserializer $currentEntityDeserializer = null
	) {
		$this->legacyFactory = new LegacyDeserializerFactory( $dataValueDeserializer, $idParser );
		$this->currentFactory = new CurrentDeserializerFactory( $dataValueDeserializer, $idParser );
		$this->currentEntityDeserializer = $currentEntityDeserializer;
	}

	/**
	 * @return Deserializer
	 */
	public function newEntityDeserializer() {
		return new EntityDeserializer(
			$this->legacyFactory->newEntityDeserializer(),
			$this->currentEntityDeserializer ?: $this->currentFactory->newEntityDeserializer()
		);
	}

	/**
	 * @since 1.1
	 * @deprecated since 1.4 - use newStatementDeserializer instead
	 *
	 * @return Deserializer
	 */
	public function newClaimDeserializer() {
		return $this->newStatementDeserializer();
	}

	/**
	 * @since 1.4
	 *
	 * @return Deserializer
	 */
	public function newStatementDeserializer() {
		return new StatementDeserializer(
			$this->legacyFactory->newStatementDeserializer(),
			$this->currentFactory->newStatementDeserializer()
		);
	}

}

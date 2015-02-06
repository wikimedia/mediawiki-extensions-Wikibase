<?php

namespace Wikibase\Client\Scribunto;

use Language;
use Wikibase\Lib\SnakFormatter;
use Wikibase\DataModel\Deserializers\SnakListDeserializer;
use Wikibase\DataModel\Deserializers\SnakDeserializer;

/**
 * Functionality needed to render snaks as provided through Lua.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class SnakRenderer {
	/**
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * @var SnakDeserializer
	 */
	private $snakDeserializer;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var SnakListDeserializer
	 */
	private $snakListDeserializer;

	/**
	 * @param SnakFormatter $snakFormatter
	 * @param SnakDeserializer $snakDeserializer
	 * @param Language $language
	 * @param SnakListDeserializer $snakListDeserializer
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		SnakDeserializer $snakDeserializer,
		Language $language,
		SnakListDeserializer $snakListDeserializer
	) {
		$this->snakFormatter = $snakFormatter;
		$this->snakDeserializer = $snakDeserializer;
		$this->language = $language;
		$this->snakListDeserializer = $snakListDeserializer;
	}

	/**
	 * Render a snak from its serialization as provided from Lua.
	 *
	 * @since 0.5
	 *
	 * @param array $snakSerialization
	 *
	 * @return string
	 */
	public function renderSnak( array $snakSerialization ) {
		$snak = $this->snakDeserializer->deserialize( $snakSerialization );
		return $this->snakFormatter->formatSnak( $snak );
	}

	/**
	 * Render a snak from its serialization as provided from Lua.
	 *
	 * @since 0.5
	 *
	 * @param array $snaksSerialization
	 *
	 * @return string
	 */
	public function renderSnaks( array $snaksSerialization ) {
		$snaks = $this->snakListDeserializer->deserialize( $snaksSerialization );

		if ( !count( $snaks ) ) {
			return '';
		}

		return $this->formatSnakList( iterator_to_array( $snaks ) );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string
	 */
	private function formatSnakList( array $snaks ) {
		$formattedValues = $this->formatSnaks( $snaks );
		return $this->language->commaList( $formattedValues );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string[]
	 */
	private function formatSnaks( array $snaks ) {
		$formattedValues = array();

		foreach ( $snaks as $snak ) {
			$formattedValues[] = $this->snakFormatter->formatSnak( $snak );
		}

		return $formattedValues;
	}
}
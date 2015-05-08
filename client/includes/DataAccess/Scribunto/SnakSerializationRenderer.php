<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Language;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Deserializers\SnakListDeserializer;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;

/**
 * Functionality needed to render snaks as provided through Lua.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class SnakSerializationRenderer {

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
	 * @param array $snakSerialization As obtained from ItemSerializer
	 *
	 * @return string wikitext
	 */
	public function renderSnak( array $snakSerialization ) {
		$snak = $this->snakDeserializer->deserialize( $snakSerialization );

		return $this->snakFormatter->formatSnak( $snak );
	}

	/**
	 * Render a list of snaks from their serialization as provided from Lua.
	 *
	 * @since 0.5
	 *
	 * @param array $snaksSerialization Nested array structure, as obtained from ItemSerializer
	 *
	 * @return string wikitext, snaks are comma separated
	 */
	public function renderSnaks( array $snaksSerialization ) {
		$snaks = $this->snakListDeserializer->deserialize( $snaksSerialization );

		if ( $snaks->isEmpty() ) {
			return '';
		}

		$snaks = iterator_to_array( $snaks );
		return $this->formatSnakList( $snaks );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string
	 */
	private function formatSnakList( array $snaks ) {
		$formattedValues = array_map(
			array( $this->snakFormatter, 'formatSnak' ),
			$snaks
		);

		return $this->language->commaList( $formattedValues );
	}

}

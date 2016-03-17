<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Deserializers\Deserializer;
use Language;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;

/**
 * Functionality needed to render snaks as provided through Lua.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class SnakSerializationRenderer {

	/**
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * @var Deserializer
	 */
	private $snakDeserializer;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var Deserializer
	 */
	private $snakListDeserializer;

	/**
	 * @param SnakFormatter $snakFormatter
	 * @param Deserializer $snakDeserializer
	 * @param Language $language
	 * @param Deserializer $snakListDeserializer
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		Deserializer $snakDeserializer,
		Language $language,
		Deserializer $snakListDeserializer
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

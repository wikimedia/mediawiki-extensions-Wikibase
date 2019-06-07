<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Deserializers\Deserializer;
use Language;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * Functionality needed to render snaks as provided through Lua.
 *
 * @license GPL-2.0-or-later
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
	 * @param array $snakSerialization As obtained from ItemSerializer
	 *
	 * @return string wikitext
	 */
	public function renderSnak( array $snakSerialization ) {
		/** @var Snak $snak */
		$snak = $this->snakDeserializer->deserialize( $snakSerialization );

		return $this->snakFormatter->formatSnak( $snak );
	}

	/**
	 * Render a list of snaks from their serialization as provided from Lua.
	 *
	 * @param array $snaksSerialization Nested array structure, as obtained from ItemSerializer
	 *
	 * @return string wikitext, snaks are comma separated
	 */
	public function renderSnaks( array $snaksSerialization ) {
		/** @var SnakList $snakList */
		$snakList = $this->snakListDeserializer->deserialize( $snaksSerialization );

		if ( $snakList->isEmpty() ) {
			return '';
		}

		$snaks = iterator_to_array( $snakList );
		return $this->formatSnakList( $snaks );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string Wikitext
	 */
	private function formatSnakList( array $snaks ) {
		$formattedValues = [];

		foreach ( $snaks as $snak ) {
			$formattedValue = $this->snakFormatter->formatSnak( $snak );

			if ( $formattedValue !== '' ) {
				$formattedValues[] = $formattedValue;
			}
		}

		$commaList = $this->language->commaList( $formattedValues );

		if ( $commaList === ''
			|| $this->snakFormatter->getFormat() === SnakFormatter::FORMAT_PLAIN
		) {
			return $commaList;
		}

		return "<span>$commaList</span>";
	}

}

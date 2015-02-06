<?php

namespace Wikibase\Client\Scribunto;

use Language;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Deserializers\SnakListDeserializer;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Entity\EntityIdValue;
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
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @param SnakFormatter $snakFormatter
	 * @param SnakDeserializer $snakDeserializer
	 * @param Language $language
	 * @param SnakListDeserializer $snakListDeserializer
	 * @param UsageAccumulator $usageAccumulator
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		SnakDeserializer $snakDeserializer,
		Language $language,
		SnakListDeserializer $snakListDeserializer,
		UsageAccumulator $usageAccumulator
	) {
		$this->snakFormatter = $snakFormatter;
		$this->snakDeserializer = $snakDeserializer;
		$this->language = $language;
		$this->snakListDeserializer = $snakListDeserializer;
		$this->usageAccumulator = $usageAccumulator;
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

		$this->trackUsage( array( $snak ) );

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
		$this->trackUsage( $snaks );
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

	/**
	 * @todo Share code with LanguageAwareRenderer::trackUsage
	 * @param Snak[] $snaks
	 */
	private function trackUsage( array $snaks ) {
		// Note: we track any EntityIdValue as a label usage.
		// This is making assumptions about what the respective formatter actually does.
		// Ideally, the formatter itself would perform the tracking, but that seems nasty to model.

		foreach ( $snaks as $snak ) {
			if ( !( $snak instanceof PropertyValueSnak ) ) {
				continue;
			}

			$value = $snak->getDataValue();

			if ( $value instanceof EntityIdValue ) {
				$this->usageAccumulator->addLabelUsage( $value->getEntityId() );
			}
		}
	}
}
<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;

/**
 * SnakFormatter decorator that records entity usage.
 *
 * @see UsageAccumulator
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class UsageTrackingSnakFormatter implements SnakFormatter {

	/**
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var string[]
	 */
	private $languages;

	/**
	 * @param SnakFormatter $snakFormatter
	 * @param UsageAccumulator $usageAccumulator
	 * @param string[] $languages language codes to consider used for formatting
	 */
	public function __construct( SnakFormatter $snakFormatter, UsageAccumulator $usageAccumulator, array $languages ) {
		$this->snakFormatter = $snakFormatter;
		$this->usageAccumulator = $usageAccumulator;
		$this->languages = $languages;
	}

	/**
	 * Formats a snak.
	 *
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function formatSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak ) {
			$value = $snak->getDataValue();

			if ( $value instanceof EntityIdValue ) {
				$entityId = $value->getEntityId();
				$this->addLabelUsage( $value->getEntityId() );
				$this->usageAccumulator->addTitleUsage( $entityId );
			}
		}

		return $this->snakFormatter->formatSnak( $snak );
	}

	/**
	 * @param EntityId $id
	 */
	private function addLabelUsage( EntityId $id ) {
		foreach ( $this->languages as $lang ) {
			$this->usageAccumulator->addLabelUsage( $id, $lang );
		}
	}

	/**
	 * Checks whether this SnakFormatter can format the given snak.
	 *
	 * @param Snak $snak
	 *
	 * @return bool
	 */
	public function canFormatSnak( Snak $snak ) {
		return $this->snakFormatter->canFormatSnak( $snak );
	}

	/**
	 * Returns the format ID of the format this formatter generates.
	 * This uses the FORMAT_XXX constants defined in OutputFormatSnakFormatterFactory.
	 *
	 * @see SnakFormatter::FORMAT_PLAIN
	 * @see SnakFormatter::FORMAT_WIKI
	 * @see SnakFormatter::FORMAT_HTML
	 * @see SnakFormatter::FORMAT_HTML_WIDGET
	 *
	 * @return string
	 */
	public function getFormat() {
		return $this->snakFormatter->getFormat();
	}

}

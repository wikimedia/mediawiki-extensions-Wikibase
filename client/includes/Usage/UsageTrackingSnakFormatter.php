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
	 * @see SnakFormatter::formatSnak
	 *
	 * @param Snak $snak
	 *
	 * @return string Either plain text, wikitext or HTML, depending on the SnakFormatter provided.
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
	 * Returns the format ID of the format this formatter generates.
	 * This uses the FORMAT_XXX constants defined in OutputFormatSnakFormatterFactory.
	 *
	 * @return string One of the SnakFormatter::FORMAT_... constants.
	 */
	public function getFormat() {
		return $this->snakFormatter->getFormat();
	}

}

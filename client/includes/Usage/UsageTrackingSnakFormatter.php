<?php

namespace Wikibase\Client\Usage;

use DataValues\UnboundedQuantityValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;

/**
 * SnakFormatter decorator that records entity usage.
 *
 * @see UsageAccumulator
 *
 * @license GPL-2.0+
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
	 * @var EntityIdParser
	 */
	private $repoItemUriParser;

	/**
	 * @param SnakFormatter $snakFormatter
	 * @param UsageAccumulator $usageAccumulator
	 * @param string[] $languages language codes to consider used for formatting
	 * @param EntityIdParser $repoItemUriParser
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		UsageAccumulator $usageAccumulator,
		array $languages,
		EntityIdParser $repoItemUriParser
	) {
		$this->snakFormatter = $snakFormatter;
		$this->usageAccumulator = $usageAccumulator;
		$this->languages = $languages;
		$this->repoItemUriParser = $repoItemUriParser;
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
				$this->addLabelUsage( $entityId );
				$this->usageAccumulator->addTitleUsage( $entityId );
			} elseif ( $value instanceof UnboundedQuantityValue ) {
				$unit = $value->getUnit();
				try {
					$entityId = $this->repoItemUriParser->parse( $unit );
				} catch ( EntityIdParsingException $e ) {
					$entityId = null;
				}
				if ( $entityId ) {
					$this->addLabelUsage( $entityId );
				}
			}
		}

		return $this->snakFormatter->formatSnak( $snak );
	}

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

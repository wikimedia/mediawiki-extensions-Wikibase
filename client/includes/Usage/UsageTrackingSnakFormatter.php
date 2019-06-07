<?php

namespace Wikibase\Client\Usage;

use DataValues\UnboundedQuantityValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * SnakFormatter decorator that records entity usage.
 * This uses an UsageTrackingLanguageFallbackLabelDescriptionLookup internally in order
 * to track label usage.
 *
 * @see UsageAccumulator
 *
 * @license GPL-2.0-or-later
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
	 * @var EntityIdParser
	 */
	private $repoItemUriParser;

	/**
	 * @var UsageTrackingLanguageFallbackLabelDescriptionLookup
	 */
	private $usageTrackingLabelDescriptionLookup;

	public function __construct(
		SnakFormatter $snakFormatter,
		UsageAccumulator $usageAccumulator,
		EntityIdParser $repoItemUriParser,
		UsageTrackingLanguageFallbackLabelDescriptionLookup $usageTrackingLabelDescriptionLookup
	) {
		$this->snakFormatter = $snakFormatter;
		$this->usageAccumulator = $usageAccumulator;
		$this->repoItemUriParser = $repoItemUriParser;
		$this->usageTrackingLabelDescriptionLookup = $usageTrackingLabelDescriptionLookup;
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

			// FIXME: All usage tracking is technically misplaced here, as it repeats knowledge from
			// WikibaseValueFormatterBuilders and what the individual formatters actually use.
			if ( $value instanceof EntityIdValue ) {
				$entityId = $value->getEntityId();
				$this->addLabelUsage( $entityId );

				// This title usage aspect must be kept in sync with what the formatters from
				// WikibaseValueFormatterBuilders::newEntityIdFormatter actually use.
				if ( $this->getFormat() !== SnakFormatter::FORMAT_PLAIN ) {
					$this->usageAccumulator->addTitleUsage( $entityId );
				}
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
		// Just get the label from UsageTrackingLanguageFallbackLabelDescriptionLookup::getLabel,
		// which will record the appropriate usage(s) for us.
		$this->usageTrackingLabelDescriptionLookup->getLabel( $id );
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

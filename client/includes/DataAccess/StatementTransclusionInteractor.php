<?php

namespace Wikibase\DataAccess;

use Language;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataAccess\PropertyIdResolver;
use Wikibase\DataAccess\SnaksFinder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Renders the main Snaks associated with a given Property on an Entity.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 *
 * @author Marius Hoch < hoo@online.de >
 */
class StatementTransclusionInteractor {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var PropertyIdResolver
	 */
	private $propertyIdResolver;

	/**
	 * @var SnaksFinder
	 */
	private $snaksFinder;

	/**
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @param Language $language
	 * @param PropertyIdResolver $propertyIdResolver
	 * @param SnaksFinder $snaksFinder
	 * @param SnakFormatter $snakFormatter
	 * @param EntityLookup $entityLookup
	 */
	public function __construct(
		Language $language,
		PropertyIdResolver $propertyIdResolver,
		SnaksFinder $snaksFinder,
		SnakFormatter $snakFormatter,
		EntityLookup $entityLookup
	) {
		$this->language = $language;
		$this->propertyIdResolver = $propertyIdResolver;
		$this->snaksFinder = $snaksFinder;
		$this->snakFormatter = $snakFormatter;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param EntityId $entityId
	 * @param UsageAccumulator $usageAccumulator
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 * @param int[]|null $acceptableRanks
	 *
	 * @throws PropertyLabelNotResolvedException
	 * @return string
	 */
	public function render( EntityId $entityId, UsageAccumulator $usageAccumulator, $propertyLabelOrId, $acceptableRanks = null ) {
		$entity = $this->entityLookup->getEntity( $entityId );

		if ( !$entity instanceof StatementListProvider ) {
			return '';
		}

		$propertyId = $this->propertyIdResolver->resolvePropertyId(
			$propertyLabelOrId,
			$this->language->getCode()
		);

		$snaks = $this->snaksFinder->findSnaks(
			$entity,
			$propertyId,
			$acceptableRanks
		);
		$this->trackUsage( $snaks, $usageAccumulator );

		return $this->formatSnaks( $snaks );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string wikitext
	 */
	private function formatSnaks( array $snaks ) {
		$formattedValues = array();

		foreach ( $snaks as $snak ) {
			$formattedValues[] = $this->snakFormatter->formatSnak( $snak );
		}

		return $this->language->commaList( $formattedValues );
	}

	/**
	 * @param Snak[] $snaks
	 * @param UsageAccumulator $usageAccumulator
	 */
	private function trackUsage( array $snaks, UsageAccumulator $usageAccumulator ) {
		// Note: we track any EntityIdValue as a label usage.
		// This is making assumptions about what the respective formatter actually does.
		// Ideally, the formatter itself would perform the tracking, but that seems nasty to model.

		foreach ( $snaks as $snak ) {
			if ( !( $snak instanceof PropertyValueSnak) ) {
				continue;
			}

			$value = $snak->getDataValue();

			if ( $value instanceof EntityIdValue ) {
				$usageAccumulator->addLabelUsage( $value->getEntityId() );
			}
		}
	}

}

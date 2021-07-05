<?php

namespace Wikibase\Client\DataAccess;

use Language;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * Renders the main Snaks associated with a given Property on an Entity.
 *
 * @license GPL-2.0-or-later
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
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	public function __construct(
		Language $language,
		PropertyIdResolver $propertyIdResolver,
		SnaksFinder $snaksFinder,
		SnakFormatter $snakFormatter,
		EntityLookup $entityLookup,
		UsageAccumulator $usageAccumulator
	) {
		$this->language = $language;
		$this->propertyIdResolver = $propertyIdResolver;
		$this->snaksFinder = $snaksFinder;
		$this->snakFormatter = $snakFormatter;
		$this->entityLookup = $entityLookup;
		$this->usageAccumulator = $usageAccumulator;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 * @param int[]|null $acceptableRanks
	 *
	 * @throws PropertyLabelNotResolvedException
	 * @return string Wikitext
	 */
	public function render(
		EntityId $entityId,
		$propertyLabelOrId,
		array $acceptableRanks = null
	) {
		try {
			$entity = $this->entityLookup->getEntity( $entityId );
		} catch ( UnresolvedEntityRedirectException $ex ) {
			// Continue as if nothing happened (for usage tracking purposes).
			$entity = null;
		}

		if ( $entity && !( $entity instanceof StatementListProvider ) ) {
			// For entities that can't have Statements, we don't need to track usage,
			// so just bail out.
			return '';
		}

		try {
			$propertyId = $this->propertyIdResolver->resolvePropertyId(
				$propertyLabelOrId,
				$this->language->getCode()
			);

			// This means we only track a statement usage if the property ID/label
			// can be resolved. This requires the property to exist!
			$this->usageAccumulator->addStatementUsage( $entityId, $propertyId );
		} catch ( PropertyLabelNotResolvedException $propertyLabelNotResolvedException ) {
			// If the entity doesn't exist, we just want to resolve the property id
			// for usage tracking purposes, so don't let the exception bubble up.
			if ( $entity !== null ) {
				throw $propertyLabelNotResolvedException;
			}
		}

		if ( $entity === null ) {
			return '';
		}

		$snaks = $this->snaksFinder->findSnaks(
			$entity,
			$propertyId,
			$acceptableRanks
		);

		return $this->formatSnaks( $snaks );
	}

	/**
	 * @param Snak[] $snaks List of main snaks, all guaranteed to belong to the same property ID.
	 *
	 * @return string wikitext
	 */
	private function formatSnaks( array $snaks ) {
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

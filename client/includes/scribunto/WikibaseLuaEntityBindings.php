<?php

namespace Wikibase\Client\Scribunto;

use Language;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Actual implementations of the functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */

class WikibaseLuaEntityBindings {

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

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var Entity[]
	 */
	private $entities = array();

	/**
	 * @param SnakFormatter $snakFormatter
	 * @param EntityLookup $entityLookup
	 * @param UsageAccumulator $usageAccumulator
	 * @param string $siteId
	 * @param Language $language
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		EntityLookup $entityLookup,
		UsageAccumulator $usageAccumulator,
		$siteId,
		Language $language,
		EntityIdParser $entityIdParser
	) {
		$this->snakFormatter = $snakFormatter;
		$this->entityLookup = $entityLookup;
		$this->usageAccumulator = $usageAccumulator;
		$this->siteId = $siteId;
		$this->language = $language;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * Get the entity for the given EntityId (cached within the class).
	 * This *might* be redundant with caching in EntityLookup, but we
	 * don't want to rely on that (per Daniel).
	 *
	 * @param EntityId $entityId
	 *
	 * @return Entity|null
	 */
	private function getEntity( EntityId $entityId ) {
		if ( !isset( $this->entities[ $entityId->getSerialization() ] ) ) {
			$this->entities[ $entityId->getSerialization() ] =
				$this->entityLookup->getEntity( $entityId );
		}

		return $this->entities[ $entityId->getSerialization() ];
	}

	/**
	 * Returns such Claims from $entity that have a main Snak for the property that
	 * is specified by $propertyLabel.
	 *
	 * @param Entity $statementListProvider
	 * @param PropertyId $propertyId
	 *
	 * @return Claims
	 */
	private function getClaimsForProperty( StatementListProvider $statementListProvider, PropertyId $propertyId ) {
		$allClaims = new Claims( $statementListProvider->getStatements() );

		return $allClaims->getClaimsForProperty( $propertyId );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string
	 */
	private function formatSnakList( array $snaks ) {
		$formattedValues = $this->formatSnaks( $snaks );
		return $this->language->commaList( $formattedValues );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string[]
	 */
	private function formatSnaks( array $snaks ) {
		$formattedValues = array();

		foreach ( $snaks as $snak ) {
			$formattedValues[] = $this->snakFormatter->formatSnak( $snak );
		}

		return $formattedValues;
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

	/**
	 * Render the main Snaks belonging to a Claim (which is identified by a PropertyId).
	 *
	 * @since 0.5
	 * @todo Share code with LanguageAwareRenderer.
	 *
	 * @param string $entityId
	 * @param string $propertyId
	 * @param int[] $acceptableRanks
	 *
	 * @return string
	 */
	public function formatPropertyValues( $entityId, $propertyId, array $acceptableRanks = null ) {
		$entityId = $this->entityIdParser->parse( $entityId );
		$propertyId = new PropertyId( $propertyId );

		$entity = $this->getEntity( $entityId );

		if ( !$entity  || !( $entity instanceof StatementListProvider ) ) {
			return '';
		}

		$claims = $this->getClaimsForProperty( $entity, $propertyId );

		if ( !$acceptableRanks ) {
			// We only want the best claims over here, so that we only show the most
			// relevant information.
			$claims = $claims->getBestClaims();
		} else {
			// ... unless the user passed in a table of acceptable ranks
			$claims = $claims->getByRanks( $acceptableRanks );
		}

		if ( $claims->isEmpty() ) {
			return '';
		}

		$snakList = $claims->getMainSnaks();

		$this->trackUsage( $snakList );
		return $this->formatSnakList( $snakList );
	}

	/**
	 * Get global site ID (e.g. "enwiki")
	 * This is basically a helper function.
	 * @TODO: Make this part of mw.site in the Scribunto extension.
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public function getGlobalSiteId() {
		return $this->siteId;
	}

}

<?php

namespace Wikibase\Client\Scribunto;

use Language;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;

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
	 * @param EntityLookup $entityLookup,
	 * @param string $siteId,
	 * @param Language $language
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		EntityLookup $entityLookup,
		$siteId,
		Language $language
	) {
		$this->snakFormatter = $snakFormatter;
		$this->entityLookup = $entityLookup;
		$this->siteId = $siteId;
		$this->language = $language;
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
	 * @param Item $item The Entity from which to get the clams
	 * @param string $propertyId A prefixed property ID.
	 *
	 * @return Claims
	 */
	private function getClaimsForProperty( Item $item, $propertyId ) {
		$allClaims = new Claims( $item->getClaims() );

		return $allClaims->getClaimsForProperty( new PropertyId( $propertyId ) );
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
	 * Render the main Snaks belonging to a Claim (which is identified by a PropertyId).
	 *
	 * @since 0.5
	 *
	 * @param string $itemId
	 * @param string $propertyId
	 * @param int[] $acceptableRanks
	 *
	 * @return string
	 */
	public function formatPropertyValues( $itemId, $propertyId, array $acceptableRanks = null ) {
		$item = $this->getEntity( new ItemId( $itemId ) );

		if ( !( $item instanceof Item ) ) {
			return '';
		}

		$claims = $this->getClaimsForProperty( $item, $propertyId );

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

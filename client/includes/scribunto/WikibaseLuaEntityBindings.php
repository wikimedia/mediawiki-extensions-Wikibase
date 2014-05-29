<?php

namespace Wikibase\Client\Scribunto;

use Language;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Entity\Entity;
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

	/* @var EntityLookup */
	private $entityLookup;

	/* @var Language */
	private $language;

	/* @var string */
	private $siteId;

	/* @var SnakFormatter */
	private $snakFormatter;

	/* @var Entity[] */
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
	 * @param Entity $entity The Entity from which to get the clams
	 * @param PropertyId $propertyId A prefixed property ID.
	 *
	 * @return Claims
	 */
	private function getClaimsForProperty( Entity $entity, PropertyId $propertyId ) {
		$allClaims = new Claims( $entity->getClaims() );

		return $allClaims->getClaimsForProperty( $propertyId );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string
	 */
	private function formatSnakList( $snaks ) {
		$formattedValues = $this->formatSnaks( $snaks );
		return $this->language->commaList( $formattedValues );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string[]
	 */
	private function formatSnaks( $snaks ) {
		$strings = array();

		foreach ( $snaks as $snak ) {
			$strings[] = $this->snakFormatter->formatSnak( $snak );
		}

		return $strings;
	}

	/**
	 * Render the main Snaks belonging to a Claim (which is identified by a PropertyId).
	 *
	 * @since 0.5
	 *
	 * @param string $entityId
	 * @param string $propertyId
	 * @param array $acceptableRanks
	 *
	 * @return string
	 */
	public function formatPropertyValues( $entityId, $propertyId, array $acceptableRanks = null ) {
		$entityId = new ItemId( $entityId );
		$propertyId = new PropertyId( $propertyId );

		$entity = $this->getEntity( $entityId );

		if ( !$entity ) {
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

<?php

namespace Wikibase\Client\Scribunto;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\SnakFormatter;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\EntityLookup;
use Wikibase\Entity;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\EntityIdFormatter;
use Language;

/**
 * Actual implementations of the functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */

class WikibaseLuaEntityBindings {

	/* @var EntityIdParser */
	protected $entityIdParser;

	/* @var EntityLookup */
	protected $entityLookup;

	/* @var EntityIdFormatter */
	protected $entityIdFormatter;

	/* @var Language */
	protected $language;

	/* @var string */
	protected $siteId;

	/* @var SnakFormatter */
	protected $snakFormatter;

	/* @var Entity[] */
	private $entities = array();

	/**
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct(
		SnakFormatter $snakFormatter,
		EntityIdParser $entityIdParser,
		EntityLookup $entityLookup,
		EntityIdFormatter $entityIdFormatter,
		$siteId,
		Language $language
	) {
		$this->snakFormatter = $snakFormatter;
		$this->entityIdParser = $entityIdParser;
		$this->entityLookup = $entityLookup;
		$this->entityIdFormatter = $entityIdFormatter;
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
	 * @param string $propertyId A prefixed property ID.
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
	 *
	 * @return array
	 */
	public function formatPropertyValues( $entityId, $propertyId ) {
		$entityId = $this->entityIdParser->parse( $entityId );
		$propertyId = $this->entityIdParser->parse( $propertyId );

		$entity = $this->getEntity( $entityId );

		if ( !$entity ) {
			return array( '' );
		}

		$claims = $this->getClaimsForProperty( $entity, $propertyId );

		if ( $claims->isEmpty() ) {
			return array( '' );
		}

		$snakList = $claims->getMainSnaks();
		return array( $this->formatSnakList( $snakList ) );
	}

	/**
	 * Get global site ID (e.g. "enwiki")
	 * This is basically a helper function.
	 * @TODO: Make this part of mw.site in the Scribunto extension.
	 *
	 * @since 0.5
	 *
	 * @return array
	 */
	public function getGlobalSiteId() {
		return array( $this->siteId );
	}
}

<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * Interface and base class for objects accumulating usage tracking information for a given page.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
abstract class UsageAccumulator {

	/**
	 * Registers usage of the given aspect of the given entity.
	 *
	 * @param EntityUsage $usage
	 */
	abstract public function addUsage( EntityUsage $usage );

	/**
	 * @param Snak[] $snaks
	 */
	public function addLabelUsageForSnaks( array $snaks ) {
		foreach ( $snaks as $snak ) {
			$this->addLabelUsageForSnak( $snak );
		}
	}

	/**
	 * @param Snak $snak
	 */
	public function addLabelUsageForSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak ) {
			$value = $snak->getDataValue();

			if ( $value instanceof EntityIdValue ) {
				$this->addLabelUsage( $value->getEntityId() );
			}
		}
	}

	/**
	 * Registers the usage of an entity's label (in the local content language).
	 *
	 * @param EntityId $id
	 */
	public function addLabelUsage( EntityId $id ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::LABEL_USAGE ) );
	}

	/**
	 * Registers the usage of an entity's local page title, e.g. to refer to
	 * the corresponding page on the local wiki.
	 *
	 * @param EntityId $id
	 */
	public function addTitleUsage( EntityId $id ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::TITLE_USAGE ) );
	}

	/**
	 * Registers the usage of an entity's sitelinks, e.g. to generate language links.
	 *
	 * @param EntityId $id
	 */
	public function addSiteLinksUsage( EntityId $id ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::SITELINK_USAGE ) );
	}

	/**
	 * Registers the usage of other (i.e. not label, sitelink, or title) of an
	 * entity (e.g. access to statements or labels in labels a language other
	 * than the content language).
	 *
	 * @param EntityId $id
	 */
	public function addOtherUsage( EntityId $id ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::OTHER_USAGE ) );
	}

	/**
	 * Registers the usage of any/all data of an entity (e.g. when accessed
	 * programmatically using Lua).
	 *
	 * @param EntityId $id
	 */
	public function addAllUsage( EntityId $id ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::ALL_USAGE ) );
	}

	/**
	 * Returns all entity usages previously registered via addXxxUsage()
	 *
	 * @return EntityUsage[]
	 */
	abstract public function getUsages();

}

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
	 * Registers the usage of entity's labels (in the given language), if the provided
	 * snaks are PropertyValueSnaks that contain EntityIdValues.
	 *
	 * @note We track any EntityIdValue as a label usage. This is making assumptions about what the
	 * respective formatter actually does. Ideally, the formatter itself would perform the tracking,
	 * but that seems nasty to model.
	 *
	 * @param Snak[] $snaks
	 * @param string|null $languageCode
	 */
	public function addLabelUsageForSnaks( array $snaks, $languageCode = null ) {
		foreach ( $snaks as $snak ) {
			$this->addLabelUsageForSnak( $snak, $languageCode );
		}
	}

	/**
	 * Registers the usage of an entity's label (in the given language), if the provided
	 * snak is a PropertyValueSnak that contains an EntityIdValue.
	 *
	 * @note We track any EntityIdValue as a label usage. This is making assumptions about what the
	 * respective formatter actually does. Ideally, the formatter itself would perform the tracking,
	 * but that seems nasty to model.
	 *
	 * @param Snak $snak
	 * @param string|null $languageCode
	 */
	public function addLabelUsageForSnak( Snak $snak, $languageCode = null ) {
		if ( $snak instanceof PropertyValueSnak ) {
			$value = $snak->getDataValue();

			if ( $value instanceof EntityIdValue ) {
				$this->addLabelUsage( $value->getEntityId(), $languageCode );
			}
		}
	}

	/**
	 * Registers the usage of an entity's label (in the given language).
	 *
	 * @param EntityId $id
	 * @param string|null $languageCode
	 */
	public function addLabelUsage( EntityId $id, $languageCode = null ) {
		$this->addUsage( new EntityUsage( $id, EntityUsage::LABEL_USAGE, $languageCode ) );
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

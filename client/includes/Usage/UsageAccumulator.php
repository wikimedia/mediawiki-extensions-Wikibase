<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface and base class for objects accumulating usage tracking information for a given page.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
abstract class UsageAccumulator {

	/**
	 * Registers usage of the given aspect of the given entity.
	 *
	 * @param EntityDataUsage $usage
	 */
	abstract public function addUsage( EntityDataUsage $usage );

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

<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * This implementation of the UsageAccumulator interface simply wraps
 * an array containing the usage information.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class HashUsageAccumulator implements UsageAccumulator {

	/**
	 * @var EntityUsage[]
	 */
	private $usages = array();

	/**
	 * Registers usage of the given aspect of the given entity.
	 *
	 * @param EntityId $id
	 * @param string $aspect Use the EntityUsage::XXX_USAGE constants.
	 */
	public function addUsage( EntityId $id, $aspect ) {
		$usage = new EntityUsage( $id, $aspect );

		$key = $usage->getIdentityString();
		$this->usages[$key] = $usage;
	}

	/**
	 * @see UsageAccumulator::getUsage()
	 *
	 * @return EntityUsage[]
	 */
	public function getUsages() {
		return $this->usages;
	}

	/**
	 * Registers the usage an entity's label (in the local content language).
	 *
	 * @param EntityId $id
	 */
	public function addLabelUsage( EntityId $id ) {
		$this->addUsage( $id, EntityUsage::LABEL_USAGE );
	}

	/**
	 * Registers the usage of an entity's local page title, e.g. to refer to
	 * the corresponding page on the local wiki.
	 *
	 * @param EntityId $id
	 */
	public function addPageUsage( EntityId $id ) {
		$this->addUsage( $id, EntityUsage::TITLE_USAGE );
	}

	/**
	 * Registers the usage of an entity's sitelinks, e.g. to generate language links.
	 *
	 * @param EntityId $id
	 */
	public function addSiteLinksUsage( EntityId $id ) {
		$this->addUsage( $id, EntityUsage::SITELINK_USAGE );
	}

	/**
	 * Registers the usage of other or all data of an entity (e.g. when accessed
	 * programmatically using Lua).
	 *
	 * @param EntityId $id
	 */
	public function addAllUsage( EntityId $id ) {
		$this->addUsage( $id, EntityUsage::ALL_USAGE );
	}

}

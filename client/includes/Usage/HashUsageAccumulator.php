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
	private $usages;

	public function __construct() {
		$this->usages = array();
	}

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
	 * @see UsageAccumulator::addLabelUsage
	 *
	 * @param EntityId $id
	 */
	public function addLabelUsage( EntityId $id ) {
		$this->addUsage( $id, EntityUsage::LABEL_USAGE );
	}

	/**
	 * @see UsageAccumulator::addTitleUsage
	 *
	 * @param EntityId $id
	 */
	public function addTitleUsage( EntityId $id ) {
		$this->addUsage( $id, EntityUsage::TITLE_USAGE );
	}

	/**
	 * @see UsageAccumulator::addSitelinksUsage
	 *
	 * @param EntityId $id
	 */
	public function addSitelinksUsage( EntityId $id ) {
		$this->addUsage( $id, EntityUsage::SITELINK_USAGE );
	}

	/**
	 * @see UsageAccumulator::addOtherUsage
	 *
	 * @param EntityId $id
	 */
	public function addOtherUsage( EntityId $id ) {
		$this->addUsage( $id, EntityUsage::OTHER_USAGE );
	}

	/**
	 * @see UsageAccumulator::addAllUsage
	 *
	 * @param EntityId $id
	 */
	public function addAllUsage( EntityId $id ) {
		$this->addUsage( $id, EntityUsage::ALL_USAGE );
	}

}

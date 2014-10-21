<?php

namespace Wikibase\Client\Usage;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityId;

/**
 * This implementation of the UsageAccumulator interface acts as a wrapper around
 * a ParserOutput object. Thus, this class encapsulates the knowledge about how usage
 * is tracked in the ParserOutput.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ParserOutputUsageAccumulator implements UsageAccumulator {

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	public function __construct( ParserOutput $parserOutput ) {

		$this->parserOutput = $parserOutput;
	}

	/**
	 * Registers usage of the given aspect of the given entity.
	 *
	 * @param EntityId $id
	 * @param string $aspect Use the EntityUsage::XXX_USAGE constants.
	 */
	public function addUsage( EntityId $id, $aspect ) {
		$usages = $this->getUsages();
		$usage = new EntityUsage( $id, $aspect );

		$key = $usage->getIdentityString();
		$usages[$key] = $usage;

		$this->parserOutput->setExtensionData( 'wikibase-entity-usage', $usages );
	}

	/**
	 * @see UsageAccumulator::getUsage()
	 *
	 * @return EntityUsage[]
	 */
	public function getUsages() {
		$usages = $this->parserOutput->getExtensionData( 'wikibase-entity-usage' );
		return $usages === null ? array() : $usages;
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

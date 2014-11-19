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

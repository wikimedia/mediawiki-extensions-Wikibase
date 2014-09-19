<?php

namespace Wikibase\Usage;

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
	 * @see UsageAccumulator::addUsage()
	 *
	 * @param EntityId $id
	 * @param $aspect
	 */
	public function addUsage( EntityId $id, $aspect ) {
		$usages = $this->getUsages();

		$usages[$aspect][] = $id;

		$this->parserOutput->setExtensionData( 'wikibase-entity-usage', $usages );
	}

	/**
	 * @see UsageAccumulator::getUsage()
	 *
	 * @return array[]
	 */
	public function getUsages() {
		$usages = $this->parserOutput->getExtensionData( 'wikibase-entity-usage' );
		return $usages === null ? array() : $usages;
	}

}
 
<?php

namespace Wikibase\Usage;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Helper object for accumulating usage tracking information for a given page.
 * Implemented as a wrapper around a ParserOutput object.
 * Thus, this class encapsulates the knowledge about how usage
 * is tracked in the ParserOutput.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageAccumulator {

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	public function __construct( ParserOutput $parserOutput ) {

		$this->parserOutput = $parserOutput;
	}

	/**
	 * @param EntityId $id
	 * @param $aspect
	 */
	public function addUsage( EntityId $id, $aspect ) {
		$usages = $this->getUsages();

		$usages[$aspect][] = $id;

		$this->parserOutput->setExtensionData( 'wikibase-entity-usage', $usages );
	}

	public function getUsages() {
		$usages = $this->parserOutput->getExtensionData( 'wikibase-entity-usage' );
		return $usages === null ? array() : $usages;
	}

}
 
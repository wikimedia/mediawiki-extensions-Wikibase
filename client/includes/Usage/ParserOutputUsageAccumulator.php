<?php

namespace Wikibase\Client\Usage;

use ParserOutput;
use Wikibase\Client\WikibaseClient;

/**
 * This implementation of the UsageAccumulator interface acts as a wrapper around
 * a ParserOutput object. Thus, this class encapsulates the knowledge about how usage
 * is tracked in the ParserOutput.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ParserOutputUsageAccumulator extends UsageAccumulator {

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @var int[]
	 */
	private $usageModifierLimits;

	public function __construct( ParserOutput $parserOutput ) {
		$this->parserOutput = $parserOutput;
		$this->usageModifierLimits = WikibaseClient::getDefaultInstance()->getSettings()->getSetting(
			'entityUsageModifierLimits'
		);
	}

	/**
	 * @see UsageAccumulator::addUsage
	 *
	 * @param EntityUsage $usage
	 */
	public function addUsage( EntityUsage $usage ) {
		$usages = $this->getUsages();
		$key = $usage->getIdentityString();
		$usages[$key] = $usage;
		$this->parserOutput->setExtensionData( 'wikibase-entity-usage', $usages );
	}

	/**
	 * @see UsageAccumulator::getUsage
	 *
	 * @return EntityUsage[]
	 */
	public function getUsages() {
		$usages = $this->parserOutput->getExtensionData( 'wikibase-entity-usage' );
		if ( $usages ) {
			return ( new UsageDeduplicator( $this->usageModifierLimits ) )->deduplicate( $usages );
		}
		return [];
	}

	/**
	 * @param int[] $usageModifierLimits associative array mapping entity type to the limit
	 */
	public function setUsageModifierLimits( array $usageModifierLimits ) {
		$this->usageModifierLimits = $usageModifierLimits;
	}

}

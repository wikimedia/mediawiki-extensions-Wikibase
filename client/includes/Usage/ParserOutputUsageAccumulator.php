<?php

namespace Wikibase\Client\Usage;

use ParserOutput;

/**
 * This implementation of the UsageAccumulator interface acts as a wrapper around
 * a ParserOutput object. Thus, this class encapsulates the knowledge about how usage
 * is tracked in the ParserOutput.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ParserOutputUsageAccumulator extends UsageAccumulator {

	/**
	 * Key used to store data in ParserOutput.  Exported for use by unit tests.
	 * @var string
	 */
	public const EXTENSION_DATA_KEY = 'wikibase-entity-usage';

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @var EntityUsageFactory
	 */
	private $entityUsageFactory;

	/**
	 * @var UsageDeduplicator
	 */
	private $usageDeduplicator;

	public function __construct(
		ParserOutput $parserOutput,
		EntityUsageFactory $entityUsageFactory,
		UsageDeduplicator $deduplicator
	) {
		$this->parserOutput = $parserOutput;
		$this->usageDeduplicator = $deduplicator;
		$this->entityUsageFactory = $entityUsageFactory;
	}

	/**
	 * @see UsageAccumulator::addUsage
	 *
	 * @param EntityUsage $usage
	 */
	public function addUsage( EntityUsage $usage ) {
		$this->parserOutput->appendExtensionData(
			self::EXTENSION_DATA_KEY, $usage->getIdentityString()
		);
	}

	/**
	 * @see UsageAccumulator::getUsage
	 *
	 * @return EntityUsage[]
	 */
	public function getUsages() {
		$usageIdentities = $this->parserOutput->getExtensionData( self::EXTENSION_DATA_KEY ) ?: [];

		$usages = [];
		foreach ( $usageIdentities as $usageIdentity => $value ) {
			$usages[] = $this->entityUsageFactory->newFromIdentity( $usageIdentity );
		}

		if ( $usages ) {
			return $this->usageDeduplicator->deduplicate( $usages );
		}
		return [];
	}

}

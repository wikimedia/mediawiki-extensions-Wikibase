<?php

namespace Wikibase\Client\Usage;

use ParserOutput;
use Wikibase\Client\WikibaseClient;

/**
 * This implementation of the UsageAccumulator interface acts as a wrapper around
 * a ParserOutput object. Thus, this class encapsulates the knowledge about how usage
 * is tracked in the ParserOutput.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ParserOutputUsageAccumulator extends UsageAccumulator {

	const EXTENSION_DATA_KEY = 'wikibase-entity-usage';

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
		UsageDeduplicator $deduplicator = null
	) {
		$this->parserOutput = $parserOutput;
		// TODO: Inject it properly
		$usageModifierLimits = WikibaseClient::getDefaultInstance()->getSettings()->getSetting(
			'entityUsageModifierLimits'
		);
		$this->usageDeduplicator = $deduplicator ?: new UsageDeduplicator( $usageModifierLimits );
		$this->entityUsageFactory = $entityUsageFactory;
	}

	/**
	 * @see UsageAccumulator::addUsage
	 *
	 * @param EntityUsage $usage
	 */
	public function addUsage( EntityUsage $usage ) {
		$usages = $this->parserOutput->getExtensionData( self::EXTENSION_DATA_KEY ) ?: [];
		$key = $usage->getIdentityString();
		$usages[$key] = null;
		$this->parserOutput->setExtensionData( self::EXTENSION_DATA_KEY, $usages );
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
			if ( $value instanceof EntityUsage ) {
				// TODO: Remove this after 2019-12-12
				// Backwards compat: We used to store actual EntityUsage objects in there
				$usages[] = $value;

				continue;
			}
			$usages[] = $this->entityUsageFactory->newFromIdentity( $usageIdentity );
		}

		if ( $usages ) {
			return $this->usageDeduplicator->deduplicate( $usages );
		}
		return [];
	}

}

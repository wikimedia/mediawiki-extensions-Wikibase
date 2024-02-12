<?php

declare( strict_types=1 );

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;

/**
 * @license GPL-2.0-or-later
 */
class RedirectTrackingUsageAccumulator extends ParserUsageAccumulator {

	/**
	 * @var UsageAccumulator
	 */
	private $innerUsageAccumulator;

	/**
	 * @var EntityRedirectTargetLookup
	 */
	private $entityRedirectTargetLookup;

	/**
	 */
	public function __construct(
		ParserUsageAccumulator $innerUsageAccumulator,
		EntityRedirectTargetLookup $entityRedirectTargetLookup
	) {
		parent::__construct( $innerUsageAccumulator->getParserOutput() );
		$this->innerUsageAccumulator = $innerUsageAccumulator;
		$this->entityRedirectTargetLookup = $entityRedirectTargetLookup;
	}

	/**
	 * Registers usage of the given aspect of the given entity.
	 *
	 * @param EntityUsage $usage
	 *
	 * @return void
	 */
	public function addUsage( EntityUsage $usage ): void {

		$redirectTarget = $this->entityRedirectTargetLookup->getRedirectForEntityId( $usage->getEntityId() );
		if ( $redirectTarget !== null ) {
			$this->addRedirectTargetUsage( $redirectTarget, $usage->getAspect(), $usage->getModifier() );
			$this->addRedirectSourceUsage( $usage->getEntityId() );
			return;
		}

		$this->innerUsageAccumulator->addUsage( $usage );
	}

	private function addRedirectTargetUsage( EntityId $redirectTarget, string $aspect, ?string $modifier ): void {
		$this->innerUsageAccumulator->addUsage( new EntityUsage( $redirectTarget, $aspect, $modifier ) );
	}

	private function addRedirectSourceUsage( EntityId $redirectSource ): void {
		$this->innerUsageAccumulator->addUsage( new EntityUsage( $redirectSource, EntityUsage::OTHER_USAGE ) );
	}

	/**
	 * Returns all entity usages previously registered via addXxxUsage()
	 *
	 * @return EntityUsage[]
	 */
	public function getUsages(): array {
		return $this->innerUsageAccumulator->getUsages();
	}
}

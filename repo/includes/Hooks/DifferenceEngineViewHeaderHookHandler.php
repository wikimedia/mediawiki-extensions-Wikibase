<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Hooks;

use DifferenceEngine;
use MediaWiki\Diff\Hook\DifferenceEngineViewHeaderHook;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;

/**
 * Hook for prefetching the terms of entities mentioned in edit summaries on diff pages.
 *
 * @license GPL-2.0-or-later
 */
class DifferenceEngineViewHeaderHookHandler implements DifferenceEngineViewHeaderHook {

	private SummaryParsingPrefetchHelper $summaryParsingPrefetcher;

	private LinkTargetEntityIdLookup $linkLookup;

	private LanguageFallbackChainFactory $languageFallbackChainFactory;

	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		SummaryParsingPrefetchHelper $summaryParsingPrefetcher
	) {
		$this->summaryParsingPrefetcher = $summaryParsingPrefetcher;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->linkLookup = $linkTargetEntityIdLookup;
	}

	public static function factory(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		PrefetchingTermLookup $prefetchingTermLookup
	): self {
		return new self(
			$languageFallbackChainFactory,
			$linkTargetEntityIdLookup,
			new SummaryParsingPrefetchHelper( $prefetchingTermLookup )
		);
	}

	/**
	 * @param DifferenceEngine $differenceEngine
	 * @return void True or no return value to continue or false to abort
	 */
	public function onDifferenceEngineViewHeader( $differenceEngine ) {
		$differenceEngine->loadRevisionData();
		$entityId = $this->linkLookup->getEntityId( $differenceEngine->getTitle() );

		if ( $entityId === null ) {
			return;
		}

		$this->summaryParsingPrefetcher->prefetchTermsForMentionedEntities(
			[ $differenceEngine->getOldRevision(), $differenceEngine->getNewRevision() ],
			$this->languageFallbackChainFactory->newFromContext( $differenceEngine->getContext() )->getFetchLanguageCodes(),
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ]
		);
	}
}

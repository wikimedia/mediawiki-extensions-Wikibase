<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\Hook\PageHistoryPager__doBatchLookupsHook;
use MediaWiki\Pager\HistoryPager;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikimedia\Rdbms\IResultWrapper;

//phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
/**
 * Hook handler for prefetching on history pages.
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
class PageHistoryPagerHookHandler implements PageHistoryPager__doBatchLookupsHook {

	private LinkTargetEntityIdLookup $linkTargetEntityIdLookup;

	private LanguageFallbackChainFactory $languageFallbackChainFactory;

	private SummaryParsingPrefetchHelper $summaryParsingPrefetchHelper;

	public function __construct(
		PrefetchingTermLookup $prefetchingLookup,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory
	) {
		$this->linkTargetEntityIdLookup = $linkTargetEntityIdLookup;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->summaryParsingPrefetchHelper = new SummaryParsingPrefetchHelper( $prefetchingLookup );
	}

	public static function factory(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		PrefetchingTermLookup $prefetchingTermLookup
	): self {
		return new self(
			$prefetchingTermLookup,
			$linkTargetEntityIdLookup,
			$languageFallbackChainFactory,
		);
	}

	/**
	 * @param HistoryPager $pager
	 * @param IResultWrapper $result
	 */
	public function onPageHistoryPager__doBatchLookups( $pager, $result ) {
		$entityId = $this->linkTargetEntityIdLookup->getEntityId( $pager->getTitle() );
		if ( $entityId === null ) {
			// XXX: This means we only prefetch when showing the edit history of an entity.
			return;
		}

		$languageFallbackChain = $this->languageFallbackChainFactory->newFromContext( $pager->getContext() );

		$this->summaryParsingPrefetchHelper->prefetchTermsForMentionedEntities(
			$result,
			$languageFallbackChain->getFetchLanguageCodes(),
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ]
		);
	}
}

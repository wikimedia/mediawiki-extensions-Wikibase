<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\Hook\PageHistoryPager__doBatchLookupsHook;
use MediaWiki\Pager\HistoryPager;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikimedia\Rdbms\IResultWrapper;

//phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
/**
 * Hook handler for prefetching on history pages.
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
class PageHistoryPagerHookHandler implements PageHistoryPager__doBatchLookupsHook {

	private LanguageFallbackChainFactory $languageFallbackChainFactory;

	private SummaryParsingPrefetchHelper $summaryParsingPrefetchHelper;

	public function __construct(
		PrefetchingTermLookup $prefetchingLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory
	) {
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->summaryParsingPrefetchHelper = new SummaryParsingPrefetchHelper( $prefetchingLookup );
	}

	public static function factory(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		PrefetchingTermLookup $prefetchingTermLookup
	): self {
		return new self(
			$prefetchingTermLookup,
			$languageFallbackChainFactory,
		);
	}

	/**
	 * @param HistoryPager $pager
	 * @param IResultWrapper $result
	 */
	public function onPageHistoryPager__doBatchLookups( $pager, $result ) {
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromContext( $pager->getContext() );

		$this->summaryParsingPrefetchHelper->prefetchTermsForMentionedEntities(
			$result,
			$languageFallbackChain->getFetchLanguageCodes(),
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ]
		);
	}
}

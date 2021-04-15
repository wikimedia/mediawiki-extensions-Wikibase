<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Hooks;

use HistoryPager;
use MediaWiki\Hook\PageHistoryPager__doBatchLookupsHook;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Repo\FederatedProperties\SummaryParsingPrefetchHelper;
use Wikimedia\Rdbms\IResultWrapper;

//phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
/**
 * Hook handler for prefetching on history pages.
 *
 * Currently only used when federated properties are enabled.
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
class PageHistoryPagerHookHandler implements PageHistoryPager__doBatchLookupsHook {

	/** @var bool */
	private $federatedPropertiesEnabled;

	/** @var LinkTargetEntityIdLookup */
	private $linkTargetEntityIdLookup;

	/** @var LanguageFallbackChainFactory */
	private $languageFallbackChainFactory;

	/** @var SummaryParsingPrefetchHelper|null */
	private $federatedPropertiesPrefetchHelper;

	/**
	 * @param bool $federatedPropertiesEnabled
	 * @param PrefetchingTermLookup $prefetchingLookup
	 * @param LinkTargetEntityIdLookup $linkTargetEntityIdLookup
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 */
	public function __construct(
		bool $federatedPropertiesEnabled,
		PrefetchingTermLookup $prefetchingLookup,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory
	) {
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->linkTargetEntityIdLookup = $linkTargetEntityIdLookup;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		if ( $federatedPropertiesEnabled ) {
			$this->federatedPropertiesPrefetchHelper = new SummaryParsingPrefetchHelper( $prefetchingLookup );
		}
	}

	public static function factory(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		PrefetchingTermLookup $prefetchingTermLookup,
		SettingsArray $repoSettings
	): self {
		return new self(
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			$prefetchingTermLookup,
			$linkTargetEntityIdLookup,
			$languageFallbackChainFactory
		);
	}

	/**
	 * @param HistoryPager $pager
	 * @param IResultWrapper $result
	 */
	public function onPageHistoryPager__doBatchLookups( $pager, $result ) {
		if ( !$this->federatedPropertiesEnabled ) {
			return;
		}

		$entityId = $this->linkTargetEntityIdLookup->getEntityId( $pager->getTitle() );
		if ( $entityId === null ) {
			return;
		}

		$languageFallbackChain = $this->languageFallbackChainFactory->newFromContext( $pager->getContext() );

		$this->federatedPropertiesPrefetchHelper->prefetchFederatedProperties(
			$result,
			$languageFallbackChain->getFetchLanguageCodes(),
			[ TermTypes::TYPE_LABEL ]
		);
	}
}

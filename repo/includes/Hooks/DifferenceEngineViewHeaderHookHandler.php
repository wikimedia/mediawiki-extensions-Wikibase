<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Hooks;

use MediaWiki\Diff\Hook\DifferenceEngineViewHeaderHook;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Repo\FederatedProperties\SummaryParsingPrefetchHelper;

/**
 * Hook for prefetching and handling federated properties before links are rendered.
 *
 * @license GPL-2.0-or-later
 */
class DifferenceEngineViewHeaderHookHandler implements DifferenceEngineViewHeaderHook {

	/**
	 * @var SummaryParsingPrefetchHelper
	 */
	private $summaryParsingPrefetcher;

	/**
	 * @var LinkTargetEntityIdLookup
	 */
	private $linkLookup;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var bool
	 */
	private $federatedPropertiesEnabled;

	/**
	 * @param bool $federatedPropertiesEnabled
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param LinkTargetEntityIdLookup $linkTargetEntityIdLookup
	 * @param SummaryParsingPrefetchHelper $summaryParsingPrefetcher
	 */
	public function __construct(
		bool $federatedPropertiesEnabled,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		SummaryParsingPrefetchHelper $summaryParsingPrefetcher
	) {
		$this->summaryParsingPrefetcher = $summaryParsingPrefetcher;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->linkLookup = $linkTargetEntityIdLookup;
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
	}

	public static function factory(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		LinkTargetEntityIdLookup $linkTargetEntityIdLookup,
		PrefetchingTermLookup $prefetchingTermLookup,
		SettingsArray $repoSettings
	): self {
		return new self(
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			$languageFallbackChainFactory,
			$linkTargetEntityIdLookup,
			new SummaryParsingPrefetchHelper( $prefetchingTermLookup )
		);
	}

	public function onDifferenceEngineViewHeader( $differenceEngine ) {

		// If federated properties is enabled,
		// prefetch the property terms that occur in the revision data of the difference engine
		if ( !$this->federatedPropertiesEnabled ) {
			return;
		}

		$differenceEngine->loadRevisionData();
		$entityId = $this->linkLookup->getEntityId( $differenceEngine->getTitle() );

		if ( $entityId === null ) {
			return;
		}

		$this->summaryParsingPrefetcher->prefetchFederatedProperties(
			[ $differenceEngine->getOldRevision(), $differenceEngine->getNewRevision() ],
			$this->languageFallbackChainFactory->newFromContext( $differenceEngine->getContext() )->getFetchLanguageCodes(),
			[ TermTypes::TYPE_LABEL ]
		);
	}
}

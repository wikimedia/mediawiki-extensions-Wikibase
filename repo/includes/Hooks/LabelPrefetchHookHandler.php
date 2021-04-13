<?php

namespace Wikibase\Repo\Hooks;

use ChangesList;
use MediaWiki\Hook\ChangesListInitRowsHook;
use Title;
use TitleFactory;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Repo\FederatedProperties\SummaryParsingPrefetchHelper;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Hook handlers for triggering prefetching of labels.
 *
 * Wikibase uses the HtmlPageLinkRendererEnd hook handler
 *
 * @see HtmlPageLinkRendererEndHookHandler
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class LabelPrefetchHookHandler implements ChangesListInitRowsHook {

	/**
	 * @var TermBuffer
	 */
	private $buffer;

	/**
	 * @var EntityIdLookup
	 */
	private $idLookup;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var string[]
	 */
	private $termTypes;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var bool
	 */
	private $federatedPropertiesEnabled;

	/**
	 * @var SummaryParsingPrefetchHelper
	 */
	private $federatedPropertiesPrefetchingHelper;

	/**
	 * @return self
	 */
	public static function factory(
		TitleFactory $titleFactory,
		EntityIdLookup $entityIdLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		PrefetchingTermLookup $prefetchingTermLookup,
		SettingsArray $repoSettings,
		TermBuffer $termBuffer
	): self {
		$termTypes = [ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_DESCRIPTION ];

		return new self(
			$termBuffer,
			$entityIdLookup,
			$titleFactory,
			$termTypes,
			$languageFallbackChainFactory,
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			new SummaryParsingPrefetchHelper( $prefetchingTermLookup )
		);
	}

	/**
	 * @param TermBuffer $buffer
	 * @param EntityIdLookup $idLookup
	 * @param TitleFactory $titleFactory
	 * @param string[] $termTypes
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param bool $federatedPropertiesEnabled
	 * @param SummaryParsingPrefetchHelper $summaryParsingPrefetchHelper
	 */
	public function __construct(
		TermBuffer $buffer,
		EntityIdLookup $idLookup,
		TitleFactory $titleFactory,
		array $termTypes,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		bool $federatedPropertiesEnabled,
		SummaryParsingPrefetchHelper $summaryParsingPrefetchHelper
	) {
		$this->buffer = $buffer;
		$this->idLookup = $idLookup;
		$this->titleFactory = $titleFactory;
		$this->termTypes = $termTypes;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->federatedPropertiesPrefetchingHelper = $summaryParsingPrefetchHelper;
	}

	/**
	 * @param ChangesList $list
	 * @param IResultWrapper|object[] $rows
	 */
	public function onChangesListInitRows( $list, $rows ): void {
		try {
			$titles = $this->getChangedTitles( $rows );
			$entityIds = $this->idLookup->getEntityIds( $titles );
			$languageCodes = $this->languageFallbackChainFactory->newFromContext( $list )
				->getFetchLanguageCodes();
			$this->buffer->prefetchTerms( $entityIds, $this->termTypes, $languageCodes );

			if ( $this->federatedPropertiesEnabled ) {
				$this->federatedPropertiesPrefetchingHelper->prefetchFederatedProperties(
					$rows,
					$languageCodes,
					$this->termTypes
				);
			}

		} catch ( StorageException $ex ) {
			wfLogWarning( __METHOD__ . ': ' . $ex->getMessage() );
		}
	}

	/**
	 * @param IResultWrapper|object[] $rows
	 *
	 * @return Title[]
	 */
	private function getChangedTitles( $rows ) {
		$titles = [];

		foreach ( $rows as $row ) {
			$titles[] = $this->titleFactory->makeTitle( $row->rc_namespace, $row->rc_title );
		}

		return $titles;
	}
}

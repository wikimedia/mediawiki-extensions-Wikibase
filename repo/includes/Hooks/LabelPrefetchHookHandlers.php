<?php

namespace Wikibase\Repo\Hooks;

use ChangesList;
use RequestContext;
use Title;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\TermIndexEntry;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * Hook handlers for triggering prefetching of labels.
 *
 * Wikibase uses the LinkBegin hook handler
 *
 * @see LinkBeginHookHandler
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class LabelPrefetchHookHandlers {

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
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @return self|null
	 */
	private static function newFromGlobalState() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$termBuffer = $wikibaseRepo->getTermBuffer();

		if ( $termBuffer === null ) {
			return null;
		}

		$termTypes = [ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_DESCRIPTION ];

		// NOTE: keep in sync with fallback chain construction in LinkBeginHookHandler::newFromGlobalState
		$context = RequestContext::getMain();
		$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();
		$languageFallbackChain = $languageFallbackChainFactory->newFromContext( $context );

		return new self(
			$termBuffer,
			$wikibaseRepo->getEntityIdLookup(),
			new TitleFactory(),
			$termTypes,
			$languageFallbackChain->getFetchLanguageCodes()
		);
	}

	/**
	 * Static handler for the ChangesListInitRows hook.
	 *
	 * @param ChangesList $list
	 * @param ResultWrapper|object[] $rows
	 *
	 * @return bool
	 */
	public static function onChangesListInitRows(
		ChangesList $list,
		$rows
	) {
		$handler = self::newFromGlobalState();

		if ( !$handler ) {
			return true;
		}

		return $handler->doChangesListInitRows( $list, $rows );
	}

	/**
	 * @param TermBuffer $buffer
	 * @param EntityIdLookup $idLookup
	 * @param TitleFactory $titleFactory
	 * @param string[] $termTypes
	 * @param string[] $languageCodes
	 */
	public function __construct(
		TermBuffer $buffer,
		EntityIdLookup $idLookup,
		TitleFactory $titleFactory,
		array $termTypes,
		array $languageCodes
	) {
		$this->buffer = $buffer;
		$this->idLookup = $idLookup;
		$this->titleFactory = $titleFactory;
		$this->termTypes = $termTypes;
		$this->languageCodes = $languageCodes;
	}

	/**
	 * @param ChangesList $list
	 * @param ResultWrapper|object[] $rows
	 *
	 * @return bool
	 */
	public function doChangesListInitRows( ChangesList $list, $rows ) {
		try {
			$titles = $this->getChangedTitles( $rows );
			$entityIds = $this->idLookup->getEntityIds( $titles );
			$this->buffer->prefetchTerms( $entityIds, $this->termTypes, $this->languageCodes );
		} catch ( StorageException $ex ) {
			wfLogWarning( __METHOD__ . ': ' . $ex->getMessage() );
		}

		return true;
	}

	/**
	 * @param ResultWrapper|object[] $rows
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

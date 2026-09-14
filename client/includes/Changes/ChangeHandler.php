<?php

declare( strict_types = 1 );
namespace Wikibase\Client\Changes;

use MediaWiki\Page\PageStore;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use Psr\Log\LoggerInterface;
use Wikibase\Client\Hooks\WikibaseClientHookRunner;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;
use Wikibase\Lib\Changes\ItemChange;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this service which then takes care handling it.
 *
 * @see @ref docs_topics_change-propagation for an overview of the change propagation mechanism.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeHandler {

	public function __construct(
		private AffectedPagesFinder $affectedPagesFinder,
		private TitleFactory $titleFactory,
		private PageStore $pageStore,
		private PageUpdater $updater,
		private ChangeRunCoalescer $changeRunCoalescer,
		private LoggerInterface $logger,
		private WikibaseClientHookRunner $hookRunner,
		private bool $injectRecentChanges,
		private string $localSiteId,
		private bool $suppressOtherLanguageLinkUpdates
	) {
	}

	/**
	 * @param EntityChange[] $changes
	 * @param array $rootJobParams any relevant root job parameters to be inherited by new jobs.
	 */
	public function handleChanges( array $changes, array $rootJobParams = [] ) {
		$changes = $this->changeRunCoalescer->transformChangeList( $changes );

		if ( !$this->hookRunner->onWikibaseHandleChanges( $changes, $rootJobParams ) ) {
			return;
		}

		foreach ( $changes as $change ) {
			if ( !$this->hookRunner->onWikibaseHandleChange( $change, $rootJobParams ) ) {
				continue;
			}

			$this->handleChange( $change, $rootJobParams );
		}
	}

	/**
	 * Main entry point for handling changes
	 *
	 * @todo process multiple changes at once!
	 *
	 * @param EntityChange $change
	 * @param array $rootJobParams any relevant root job parameters to be inherited by new jobs.
	 */
	public function handleChange( EntityChange $change, array $rootJobParams = [] ) {
		$changeId = $this->getChangeIdForLog( $change );

		$this->logger->debug(
			'{method}: handling change #{changeId} ({changeType})',
			[
				'method' => __METHOD__,
				'changeId' => $changeId,
				'changeType' => $change->getType(),
			]
		);

		if ( $this->suppressOtherLanguageLinkUpdates && $this->isOtherWikiLanguageLinkChange( $change ) ) {
			$this->logger->debug(
				'{method}: dropping change #{changeId} as an other-language sitelink',
				[
					'method' => __METHOD__,
					'changeId' => $changeId,
				]
			);
			return;
		}

		$usagesPerPage = $this->affectedPagesFinder->getAffectedUsagesByPage( $change );

		$this->logger->debug(
			'{method}: updating {pageCount} page(s) for change #{changeId}.',
			[
				'method' => __METHOD__,
				'changeId' => $changeId,
				'pageCount' => count( $usagesPerPage ),
			]
		);

		// if no usages we can abort early
		if ( $usagesPerPage === [] ) {
			return;
		}

		// Run all updates on all affected pages
		$titlesToUpdate = $this->getTitlesForUsages( $usagesPerPage );

		// if no titles we can abort early
		if ( $titlesToUpdate === [] ) {
			return;
		}

		// NOTE: deduplicate
		$titleBatchSignature = $this->getTitleBatchSignature( $titlesToUpdate );
		$rootJobParams['rootJobSignature'] = $titleBatchSignature;

		if ( !isset( $rootJobParams['rootJobTimestamp'] ) ) {
			$rootJobParams['rootJobTimestamp'] = wfTimestampNow();
		}

		$this->updater->purgeWebCache(
			$titlesToUpdate,
			$rootJobParams,
			$change->getAction(),
			$change->hasField( ChangeRow::USER_ID ) ? 'uid:' . $change->getUserId() : 'uid:?'
		);

		// Removing root job timestamp to make it work: T233520
		$refreshLinksRootParams = $rootJobParams;
		unset( $refreshLinksRootParams['rootJobTimestamp'] );

		$this->updater->scheduleRefreshLinks(
			$titlesToUpdate,
			$refreshLinksRootParams,
			$change->getAction(),
			'uid:' . ( $change->getUserId() ?: '?' )
		);

		// NOTE: signature depends on change ID, effectively disabling deduplication
		$changeSignature = $this->getChangeSignature( $change );
		$rootJobParams['rootJobSignature'] = $titleBatchSignature . '&' . $changeSignature;
		if ( $this->injectRecentChanges ) {
			$this->updater->injectRCRecords( $titlesToUpdate, $change, $rootJobParams );
		}
	}

	/**
	 * @param Title[] $titles
	 *
	 * @return string a signature based on the hash of the given titles
	 */
	private function getTitleBatchSignature( array $titles ) {
		$pages = [];

		/** @see WikiPageUpdater::getPageParamForRefreshLinksJob */
		foreach ( $titles as $title ) {
			$id = $title->getArticleID();
			$pages[$id] = [ $title->getNamespace(), $title->getDBkey() ];
		}

		ksort( $pages );
		return 'title-batch:' . sha1( json_encode( $pages ) );
	}

	/**
	 * @param EntityChange $change
	 *
	 * @return string a signature representing the change's identity.
	 */
	private function getChangeSignature( EntityChange $change ) {
		if ( $change->getId() ) {
			return 'change-id:' . $change->getId();
		} else {
			// synthetic change!
			$changeData = $change->getFields();

			if ( isset( $changeData[ChangeRow::INFO]['change-ids'] ) ) {
				$ids = $changeData[ChangeRow::INFO]['change-ids'];
				sort( $ids );
				return 'change-batch:' . implode( ',', $ids );
			} else {
				ksort( $changeData );
				return 'change-hash:' . sha1( json_encode( $changeData ) );
			}
		}
	}

	/**
	 * @param PageEntityUsages[] $usagesPerPage
	 *
	 * @return Title[]
	 */
	private function getTitlesForUsages( $usagesPerPage ) {
		$pageIds = [];

		foreach ( $usagesPerPage as $usages ) {
			$pageIds[] = $usages->getPageId();
		}

		$pageRecords = $this->pageStore
			->newSelectQueryBuilder()
			->wherePageIds( $pageIds )
			->caller( __METHOD__ )
			->fetchPageRecords();

		return array_map(
			[ $this->titleFactory, 'newFromPageIdentity' ],
			iterator_to_array( $pageRecords )
		);
	}

	/**
	 * Returns a human readable change ID, containing multiple IDs in case of a
	 * coalesced change.
	 *
	 * @param Change $change
	 *
	 * @return string
	 */
	private function getChangeIdForLog( Change $change ) {
		if ( $change instanceof EntityChange ) {
			$info = $change->getInfo();

			if ( isset( $info['change-ids'] ) ) {
				return implode( '|', $info['change-ids'] );
			}
		}

		return (string)$change->getId();
	}

	/**
	 * Returns true when the change is a language link update which doesn't
	 * directly affect the current client wiki.
	 */
	private function isOtherWikiLanguageLinkChange( EntityChange $change ): bool {
		// Only test Item changes.
		if ( !( $change instanceof ItemChange ) ) {
			return false;
		}

		// Don't suppress any change other than sitelinks.
		$aspects = $change->getCompactDiff();
		if ( !( $aspects instanceof EntityDiffChangedAspects ) ||
			$aspects->getAliasChanges() !== [] ||
			$aspects->getDescriptionChanges() !== [] ||
			$aspects->getLabelChanges() !== [] ||
			$aspects->getStatementChanges() !== [] ||
			$aspects->hasOtherChanges()
		) {
			return false;
		}

		// There must be a sitelink change.
		$siteLinkDiff = $change->getSiteLinkDiff();
		if ( $siteLinkDiff->isEmpty() ) {
			return false;
		}

		// Now test whether the changes include the sitelink to this wiki, or else
		// they are "other".
		$diffOps = $siteLinkDiff->getOperations();
		return !array_key_exists( $this->localSiteId, $diffOps );
	}
}

<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use InvalidArgumentException;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\Hook\LinksUpdateCompleteHook;
use MediaWiki\Hook\ParserCacheSaveCompleteHook;
use MediaWiki\JobQueue\JobQueueGroup;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Parser\ParserCache;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Permissions\Authority;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Wikibase\Client\ParserOutput\ScopedParserOutputProvider;
use Wikibase\Client\Store\AddUsagesForPageJob;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageLookup;

/**
 * Hook handlers for triggering data updates.
 *
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of DataUpdateHookHandler and then call the
 * corresponding member function on that.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class DataUpdateHookHandler implements
	LinksUpdateCompleteHook,
	PageDeleteCompleteHook,
	ParserCacheSaveCompleteHook
{

	/**
	 * @var UsageUpdater
	 */
	private $usageUpdater;

	/**
	 * @var JobQueueGroup
	 */
	private $jobScheduler;

	/**
	 * @var UsageLookup
	 */
	private $usageLookup;

	/**
	 * @var UsageAccumulatorFactory
	 */
	private $usageAccumulatorFactory;

	/** @var LoggerInterface */
	private $logger;

	public static function factory(
		JobQueueGroup $jobQueueGroup,
		LoggerInterface $logger,
		ClientStore $store,
		UsageAccumulatorFactory $usageAccumulatorFactory
	): self {
		return new self(
			$store->getUsageUpdater(),
			$jobQueueGroup,
			$store->getUsageLookup(),
			$usageAccumulatorFactory,
			$logger
		);
	}

	public function __construct(
		UsageUpdater $usageUpdater,
		JobQueueGroup $jobScheduler,
		UsageLookup $usageLookup,
		UsageAccumulatorFactory $usageAccumulatorFactory,
		?LoggerInterface $logger = null
	) {
		$this->usageUpdater = $usageUpdater;
		$this->jobScheduler = $jobScheduler;
		$this->usageLookup = $usageLookup;
		$this->usageAccumulatorFactory = $usageAccumulatorFactory;
		$this->logger = $logger ?: new NullLogger();
	}

	/** @inheritDoc */
	public function onPageDeleteComplete(
		ProperPageIdentity $page,
		Authority $deleter,
		string $reason,
		int $pageID,
		RevisionRecord $deletedRev,
		ManualLogEntry $logEntry,
		int $archivedRevisionCount
	) {
		DeferredUpdates::addCallableUpdate( function () use ( $pageID ) {
			$this->usageUpdater->pruneUsagesForPage( $pageID );
		} );
	}

	/**
	 * Triggered when a page gets re-rendered to update secondary link tables.
	 * Implemented to update usage tracking information via UsageUpdater.
	 *
	 * @param LinksUpdate $linksUpdate
	 * @param mixed $ticket Prior result of LBFactory::getEmptyTransactionTicket()
	 */
	public function onLinksUpdateComplete( $linksUpdate, $ticket ): void {
		// Tests fail because when repo is not loaded, it tries to connect to repo's database
		if (
			!ExtensionRegistry::getInstance()->isLoaded( 'WikibaseRepository' ) &&
			defined( 'MW_PHPUNIT_TEST' )
		) {
			return;
		}

		$this->doLinksUpdateComplete( $linksUpdate );
	}

	public function doLinksUpdateComplete( LinksUpdate $linksUpdate ): void {
		$pageId = $linksUpdate->getPageId();
		if ( !$pageId ) {
			$this->logger->info(
				__METHOD__ . ': skipping page ID {pageId} for title {title} (T264929)',
				[
					'pageId' => $pageId,
					'title' => $linksUpdate->getTitle()->getPrefixedText(),
					'causeAction' => $linksUpdate->getCauseAction(),
					'exception' => new RuntimeException(),
				]
			);
			return;
		}

		$parserOutputProvider = new ScopedParserOutputProvider( $linksUpdate->getParserOutput() );
		$usageAcc = $this->usageAccumulatorFactory->newFromParserOutputProvider( $parserOutputProvider );

		// Please note that page views that happen between the page save but before this is run will have
		// their usages removed (as we might add the usages via onParserCacheSaveComplete before this is run).
		$this->usageUpdater->replaceUsagesForPage( $pageId, $usageAcc->getUsages() );
		$parserOutputProvider->close();
	}

	/**
	 * Triggered when a new rendering of a page is committed to the ParserCache.
	 * Implemented to update usage tracking information via UsageUpdater.
	 *
	 * @param ParserCache $parserCache ParserCache object $parserOutput was stored in
	 * @param ParserOutput $parserOutput ParserOutput object that was stored
	 * @param Title $title Title of the page that was parsed to generate $parserOutput
	 * @param ParserOptions $popts ParserOptions used for generating $parserOutput
	 * @param int $revId ID of the revision that was parsed to create $parserOutput
	 */
	public function onParserCacheSaveComplete( $parserCache, $parserOutput, $title, $popts, $revId ): void {
		DeferredUpdates::addCallableUpdate( function () use ( $parserOutput, $title ) {
			$parserOutputProvider = new ScopedParserOutputProvider( $parserOutput );
			$usageAcc = $this->usageAccumulatorFactory->newFromParserOutputProvider( $parserOutputProvider );

			$usages = $this->reindexEntityUsages( $usageAcc->getUsages() );
			$parserOutputProvider->close();
			if ( $usages === [] ) {
				// no usages or no title, bail out
				return;
			}

			// Add any usages present in the new rendering.
			// This allows us to track usages in each user language separately, for multilingual sites.

			// NOTE: Since parser cache updates may be triggered by page views (in a new language),
			// schedule the usage updates in the job queue, to avoid writing to the database
			// during a GET request.

			$currentUsages = $this->usageLookup->getUsagesForPage( $title->getArticleID() );
			$newUsages = array_diff_key( $usages, $currentUsages );
			if ( $newUsages === [] ) {
				return;
			}

			$addUsagesForPageJob = AddUsagesForPageJob::newSpec( $title, $newUsages );
			$this->jobScheduler->push( $addUsagesForPageJob );
		} );
	}

	/**
	 * Re-indexes the given list of EntityUsages so that each EntityUsage can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	private function reindexEntityUsages( array $usages ): array {
		$reindexed = [];

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$key = $usage->getIdentityString();
			$reindexed[$key] = $usage;
		}

		return $reindexed;
	}
}

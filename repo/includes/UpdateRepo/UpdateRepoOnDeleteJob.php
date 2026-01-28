<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\UpdateRepo;

use MediaWiki\Language\FormatterFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Site\SiteLookup;
use MediaWiki\Title\Title;
use OutOfBoundsException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Summary;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;

/**
 * Job for updating the repo after a page on the client has been deleted.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDeleteJob extends UpdateRepoJob {

	/**
	 * Constructs a UpdateRepoOnMoveJob propagating a page move to the repo
	 *
	 * @note This is for use by Job::factory, don't call it directly;
	 *           use newFrom*() instead.
	 *
	 * @see Job::factory
	 * @see UpdateRepoJob::__construct
	 */
	public function __construct(
		Title $title,
		array $params,
		FormatterFactory $formatterFactory,
		MediaWikiEditEntityFactory $editEntityFactory,
		EntityStore $entityStore,
		SettingsArray $repoSettings,
		EntityLookup $entityLookup,
		SummaryFormatter $summaryFormatter,
		private readonly SiteLookup $siteLookup,
		LoggerInterface $logger = new NullLogger(),
	) {
		parent::__construct(
			'UpdateRepoOnDelete',
			$title,
			$params,
			$formatterFactory,
			$editEntityFactory,
			$entityStore,
			$repoSettings,
			$entityLookup,
			$summaryFormatter,
			$logger,
		);
	}

	public static function newFromGlobalState(
		Title $title,
		array $params,
		FormatterFactory $formatterFactory,
		SiteLookup $siteLookup,
		MediaWikiEditEntityFactory $editEntityFactory,
		EntityStore $entityStore,
		SettingsArray $repoSettings,
		Store $store,
		SummaryFormatter $summaryFormatter,
	): self {
		return new self(
			$title,
			$params,
			$formatterFactory,
			$editEntityFactory,
			$entityStore,
			$repoSettings,
			$store->getEntityLookup(
				Store::LOOKUP_CACHING_DISABLED,
				LookupConstants::LATEST_FROM_MASTER,
			),
			$summaryFormatter,
			$siteLookup,
			LoggerFactory::getInstance( 'UpdateRepo' ),
		);
	}

	/**
	 * Get a SiteLink for a specific item and site
	 */
	private function getSiteLink( Item $item, string $globalId ): ?SiteLink {
		try {
			return $item->getSiteLinkList()->getBySiteId( $globalId );
		} catch ( OutOfBoundsException ) {
			return null;
		}
	}

	/**
	 * Get a Summary object for the edit
	 */
	public function getSummary(): FormatableSummary {
		$params = $this->getParams();
		$siteId = $params['siteId'];
		$page = $params['title'];

		return new Summary(
			'clientsitelink',
			'remove',
			null,
			[ $siteId ],
			[ $page ]
		);
	}

	/**
	 * Whether the propagated update is valid (and thus should be applied)
	 */
	protected function verifyValid( Item $item ): bool {
		$params = $this->getParams();
		$siteId = $params['siteId'];
		$page = $params['title'];

		$siteLink = $this->getSiteLink( $item, $siteId );
		if ( !$siteLink || $siteLink->getPageName() !== $page ) {
			// Probably something changed since the job has been inserted
			$this->logger->debug(
				'OnDelete: The site link to {siteId} is no longer {page}',
				[
					'siteId' => $siteId,
					'page' => $page,
				]
			);
			return false;
		}

		$site = $this->siteLookup->getSite( $siteId );

		// Maybe the page has been undeleted/ recreated?
		$exists = $site->normalizePageName( $page );
		if ( $exists !== false ) {
			$this->logger->debug(
				'OnDelete: {page} on {siteId} exists',
				[
					'siteId' => $siteId,
					'page' => $page,
				]
			);
			return false;
		}

		return true;
	}

	protected function applyChanges( Item $item ): bool {
		$params = $this->getParams();
		$siteId = $params['siteId'];

		$item->getSiteLinkList()->removeLinkWithSiteId( $siteId );
		return true;
	}

}

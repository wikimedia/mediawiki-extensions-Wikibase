<?php

namespace Wikibase\Repo\UpdateRepo;

use MediaWiki\Logger\LoggerFactory;
use OutOfBoundsException;
use Psr\Log\LoggerInterface;
use SiteLookup;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Summary;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * Job for updating the repo after a page on the client has been deleted.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDeleteJob extends UpdateRepoJob {

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * Constructs a UpdateRepoOnMoveJob propagating a page move to the repo
	 *
	 * @note This is for use by Job::factory, don't call it directly;
	 *           use newFrom*() instead.
	 *
	 * @note the constructor's signature is dictated by Job::factory, so we'll have to
	 *           live with it even though it's rather ugly for our use case.
	 *
	 * @see Job::factory
	 * @see UpdateRepoJob::__construct
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'UpdateRepoOnDelete', $title, $params );

		$this->initRepoJobServicesFromGlobalState();
	}

	protected function initRepoJobServicesFromGlobalState() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			WikibaseRepo::getStore()->getEntityLookup(
				Store::LOOKUP_CACHING_DISABLED,
				LookupConstants::LATEST_FROM_MASTER
			),
			WikibaseRepo::getEntityStore(),
			$wikibaseRepo->getSummaryFormatter(),
			LoggerFactory::getInstance( 'UpdateRepo' ),
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->newEditEntityFactory()
		);
	}

	public function initServices(
		EntityLookup $entityLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter,
		LoggerInterface $logger,
		SiteLookup $siteLookup,
		MediawikiEditEntityFactory $editEntityFactory
	) {
		$this->initRepoJobServices(
			$entityLookup,
			$entityStore,
			$summaryFormatter,
			$logger,
			$editEntityFactory
		);
		$this->siteLookup = $siteLookup;
	}

	/**
	 * Get a SiteLink for a specific item and site
	 *
	 * @param Item $item
	 * @param string $globalId
	 *
	 * @return SiteLink|null
	 */
	private function getSiteLink( Item $item, $globalId ) {
		try {
			return $item->getSiteLinkList()->getBySiteId( $globalId );
		} catch ( OutOfBoundsException $e ) {
			return null;
		}
	}

	/**
	 * Get a Summary object for the edit
	 *
	 * @return Summary
	 */
	public function getSummary() {
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
	 *
	 * @param Item $item
	 *
	 * @return bool
	 */
	protected function verifyValid( Item $item ) {
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

	/**
	 * @inheritDoc
	 */
	protected function applyChanges( Item $item ) {
		$params = $this->getParams();
		$siteId = $params['siteId'];

		$item->getSiteLinkList()->removeLinkWithSiteId( $siteId );
		return true;
	}

}

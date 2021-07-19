<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\UpdateRepo;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use OutOfBoundsException;
use Psr\Log\LoggerInterface;
use SiteLookup;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Summary;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * Job for updating the repo after a page on the client has been moved.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveJob extends UpdateRepoJob {

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var string|bool|null
	 */
	private $normalizedPageName = null;

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
		parent::__construct( 'UpdateRepoOnMove', $title, $params );

		$this->initRepoJobServicesFromGlobalState();
	}

	protected function initRepoJobServicesFromGlobalState(): void {
		$services = MediaWikiServices::getInstance();

		$this->initServices(
			WikibaseRepo::getStore( $services )->getEntityLookup(
				Store::LOOKUP_CACHING_DISABLED,
				LookupConstants::LATEST_FROM_MASTER
			),
			WikibaseRepo::getEntityStore( $services ),
			WikibaseRepo::getSummaryFormatter( $services ),
			LoggerFactory::getInstance( 'UpdateRepo' ),
			$services->getSiteLookup(),
			WikibaseRepo::getEditEntityFactory( $services ),
			WikibaseRepo::getSettings( $services )
		);
	}

	public function initServices(
		EntityLookup $entityLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter,
		LoggerInterface $logger,
		SiteLookup $siteLookup,
		MediawikiEditEntityFactory $editEntityFactory,
		SettingsArray $settings
	): void {
		$this->initRepoJobServices(
			$entityLookup,
			$entityStore,
			$summaryFormatter,
			$logger,
			$editEntityFactory,
			$settings
		);
		$this->siteLookup = $siteLookup;
	}

	/**
	 * Get a SiteLink for a specific item and site
	 */
	private function getSiteLink( Item $item, string $globalId ): ?SiteLink {
		try {
			return $item->getSiteLinkList()->getBySiteId( $globalId );
		} catch ( OutOfBoundsException $e ) {
			return null;
		}
	}

	/**
	 * Get a Summary object for the edit
	 */
	public function getSummary(): FormatableSummary {
		$params = $this->getParams();
		$siteId = $params['siteId'];
		$oldPage = $params['oldTitle'];
		$newPage = $params['newTitle'];

		return new Summary(
			'clientsitelink',
			'update',
			$siteId,
			[
				$siteId . ":$oldPage",
				$siteId . ":$newPage",
			]
		);
	}

	/**
	 * @return string|bool False in case the normalization failed
	 */
	private function getNormalizedPageName() {
		if ( $this->normalizedPageName === null ) {
			$params = $this->getParams();
			$newPage = $params['newTitle'];
			$siteId = $params['siteId'];

			$site = $this->siteLookup->getSite( $siteId );
			$this->normalizedPageName = $site->normalizePageName( $newPage );

			if ( $this->normalizedPageName === false ) {
				$this->logger->debug(
					'OnMove: Normalizing the page name {newPage} on {siteId} failed',
					[
						'newPage' => $newPage,
						'siteId' => $siteId,
					]
				);
			}

		}

		return $this->normalizedPageName;
	}

	/**
	 * Whether the propagated update is valid (and thus should be applied)
	 */
	protected function verifyValid( Item $item ): bool {
		$params = $this->getParams();
		$siteId = $params['siteId'];
		$oldPage = $params['oldTitle'];

		$oldSiteLink = $this->getSiteLink( $item, $siteId );
		if ( !$oldSiteLink || $oldSiteLink->getPageName() !== $oldPage ) {
			// Probably something changed since the job has been inserted
			$this->logger->debug(
				'OnMove: The site link to {siteId} is no longer {oldPage}',
				[
					'siteId' => $siteId,
					'oldPage' => $oldPage,
				]
			);
			return false;
		}

		// Normalize the name, just in case the page has been updated in the mean time
		if ( $this->getNormalizedPageName() === false ) {
			return false;
		}

		return true;
	}

	protected function applyChanges( Item $item ): bool {
		$params = $this->getParams();
		$siteId = $params['siteId'];
		$oldSiteLink = $this->getSiteLink( $item, $siteId );

		$siteLink = new SiteLink(
			$siteId,
			$this->getNormalizedPageName(),
			$oldSiteLink->getBadges() // Keep badges
		);

		$item->getSiteLinkList()->removeLinkWithSiteId( $siteId );
		$item->getSiteLinkList()->addSiteLink( $siteLink );

		return true;
	}

}

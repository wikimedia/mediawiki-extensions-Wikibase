<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Site;
use SiteList;
use Status;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * API module to associate two pages on two different sites with a Wikibase item.
 * Requires API write mode to be enabled.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Addshore
 */
class LinkTitles extends ApiBase {

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @see ApiBase::__construct
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param ApiErrorReporter $errorReporter
	 * @param array $siteLinkGroups
	 * @param EntityRevisionLookup $revisionLookup
	 * @param callable $resultBuilderInstantiator
	 * @param callable $entitySavingHelperInstantiator
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		ApiErrorReporter $errorReporter,
		array $siteLinkGroups,
		EntityRevisionLookup $revisionLookup,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->errorReporter = $errorReporter;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->revisionLookup = $revisionLookup;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 */
	public function execute() {
		$lookup = $this->revisionLookup;

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		// Sites are already tested through allowed params ;)
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		/** @var Site $fromSite */
		list( $fromSite, $fromPage ) = $this->getSiteAndNormalizedPageName(
			$sites,
			$params['fromsite'],
			$params['fromtitle']
		);
		/** @var Site $toSite */
		list( $toSite, $toPage ) = $this->getSiteAndNormalizedPageName(
			$sites,
			$params['tosite'],
			$params['totitle']
		);

		$siteLinkStore = WikibaseRepo::getDefaultInstance()->getStore()->newSiteLinkStore();
		$fromId = $siteLinkStore->getItemIdForLink( $fromSite->getGlobalId(), $fromPage );
		$toId = $siteLinkStore->getItemIdForLink( $toSite->getGlobalId(), $toPage );

		$siteLinkList = new SiteLinkList();
		$flags = 0;
		$item = null;

		$summary = new Summary( $this->getModuleName() );
		$summary->addAutoSummaryArgs(
			$fromSite->getGlobalId() . ':' . $fromPage,
			$toSite->getGlobalId() . ':' . $toPage );

		//FIXME: use ChangeOps for consistency!

		// Figure out which parts to use and what to create anew
		if ( $fromId === null && $toId === null ) {
			// create new item
			$item = new Item();
			$toLink = new SiteLink( $toSite->getGlobalId(), $toPage );
			$item->addSiteLink( $toLink );
			$siteLinkList->addSiteLink( $toLink );
			$fromLink = new SiteLink( $fromSite->getGlobalId(), $fromPage );
			$item->addSiteLink( $fromLink );
			$siteLinkList->addSiteLink( $fromLink );

			$flags |= EDIT_NEW;
			$summary->setAction( 'create' );
		} elseif ( $fromId === null && $toId !== null ) {
			// reuse to-site's item
			/** @var Item $item */
			$itemRev = $lookup->getEntityRevision( $toId, 0, EntityRevisionLookup::LATEST_FROM_MASTER );
			$item = $itemRev->getEntity();
			$fromLink = new SiteLink( $fromSite->getGlobalId(), $fromPage );
			$item->addSiteLink( $fromLink );
			$siteLinkList->addSiteLink( $fromLink );
			$summary->setAction( 'connect' );
		} elseif ( $fromId !== null && $toId === null ) {
			// reuse from-site's item
			/** @var Item $item */
			$itemRev = $lookup->getEntityRevision( $fromId, 0, EntityRevisionLookup::LATEST_FROM_MASTER );
			$item = $itemRev->getEntity();
			$toLink = new SiteLink( $toSite->getGlobalId(), $toPage );
			$item->addSiteLink( $toLink );
			$siteLinkList->addSiteLink( $toLink );
			$summary->setAction( 'connect' );
		} elseif ( $fromId->equals( $toId ) ) {
			// no-op
			$this->errorReporter->dieError( 'Common item detected, sitelinks are both on the same item', 'common-item' );
		} else {
			// dissimilar items
			$this->errorReporter->dieError( 'No common item detected, unable to link titles', 'no-common-item' );
		}

		$this->resultBuilder->addSiteLinkList( $siteLinkList, 'entity' );
		$status = $this->getAttemptSaveStatus( $item, $summary, $flags );
		$this->buildResult( $item, $status );
	}

	/**
	 * @param SiteList $sites
	 * @param string $site
	 * @param string $pageTitle
	 *
	 * @return array( Site $site, string $pageName )
	 */
	private function getSiteAndNormalizedPageName( SiteList $sites, $site, $pageTitle ) {
		$siteObj = $sites->getSite( $site );
		$page = $siteObj->normalizePageName( $pageTitle );
		if ( $page === false ) {
			$this->errorReporter->dieWithError(
				[ 'wikibase-api-no-external-page', $site, $pageTitle ],
				'no-external-page'
			);
		}

		return [ $siteObj, $page ];
	}

	/**
	 * @param Item|null $item
	 * @param Summary $summary
	 * @param int $flags
	 *
	 * @return Status
	 */
	private function getAttemptSaveStatus( Item $item = null, Summary $summary, $flags ) {
		if ( $item === null ) {
			// to not have an Item isn't really bad at this point
			return Status::newGood( true );
		} else {
			// Do the actual save, or if it don't exist yet create it.
			return $this->entitySavingHelper->attemptSaveEntity( $item, $summary, $flags );
		}
	}

	private function buildResult( Item $item = null, Status $status ) {
		if ( $item !== null ) {
			$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'entity' );
			$this->resultBuilder->addBasicEntityInformation( $item->getId(), 'entity' );
		}

		$this->resultBuilder->markSuccess( $status->isOK() );
	}

	/**
	 * @see ModifyEntity::validateParameters
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		if ( $params['fromsite'] === $params['tosite'] ) {
			$this->errorReporter->dieError( 'The from site cannot match the to site', 'param-illegal' );
		}

		if ( $params['fromtitle'] === '' || $params['totitle'] === '' ) {
			$this->errorReporter->dieError( 'The from title and to title must have a value', 'param-illegal' );
		}
	}

	/**
	 * @see ApiBase::isWriteMode
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		return array_merge( parent::getAllowedParams(), [
			'tosite' => [
				self::PARAM_TYPE => $sites->getGlobalIdentifiers(),
			],
			'totitle' => [
				self::PARAM_TYPE => 'string',
			],
			'fromsite' => [
				self::PARAM_TYPE => $sites->getGlobalIdentifiers(),
			],
			'fromtitle' => [
				self::PARAM_TYPE => 'string',
			],
			'token' => null,
			'bot' => false,
		] );
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			'action=wblinktitles&fromsite=enwiki&fromtitle=Hydrogen&tosite=dewiki&totitle=Wasserstoff'
			=> 'apihelp-wblinktitles-example-1',
		];
	}

}

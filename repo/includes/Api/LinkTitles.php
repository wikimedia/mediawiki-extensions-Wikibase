<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Site;
use SiteList;
use Status;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Lib\Summary;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Store\Store;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module to associate two pages on two different sites with a Wikibase item.
 * Requires API write mode to be enabled.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Addshore
 */
class LinkTitles extends ApiBase {

	/** @var SiteLinkStore */
	private $siteLinkStore;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var SiteLinkGlobalIdentifiersProvider
	 */
	private $siteLinkGlobalIdentifiersProvider;

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

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		SiteLinkStore $siteLinkStore,
		SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		ApiErrorReporter $errorReporter,
		array $siteLinkGroups,
		EntityRevisionLookup $revisionLookup,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->siteLinkStore = $siteLinkStore;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->siteLinkGlobalIdentifiersProvider = $siteLinkGlobalIdentifiersProvider;
		$this->errorReporter = $errorReporter;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->revisionLookup = $revisionLookup;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		SettingsArray $repoSettings,
		SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		Store $store
	): self {

		return new self(
			$mainModule,
			$moduleName,
			// TODO move SiteLinkStore to service container and inject it directly
			$store->newSiteLinkStore(),
			$siteLinkGlobalIdentifiersProvider,
			$siteLinkTargetProvider,
			$apiHelperFactory->getErrorReporter( $mainModule ),
			$repoSettings->getSetting( 'siteLinkGroups' ),
			$store->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getResultBuilder( $module );
			},
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getEntitySavingHelper( $module );
			}
		);
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 */
	public function execute(): void {
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

		$fromId = $this->siteLinkStore->getItemIdForLink( $fromSite->getGlobalId(), $fromPage );
		$toId = $this->siteLinkStore->getItemIdForLink( $toSite->getGlobalId(), $toPage );

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
			$itemRev = $lookup->getEntityRevision( $toId, 0, LookupConstants::LATEST_FROM_MASTER );
			$item = $itemRev->getEntity();
			'@phan-var Item $item';
			$fromLink = new SiteLink( $fromSite->getGlobalId(), $fromPage );
			$item->addSiteLink( $fromLink );
			$siteLinkList->addSiteLink( $fromLink );
			$summary->setAction( 'connect' );
		} elseif ( $fromId !== null && $toId === null ) {
			// reuse from-site's item
			/** @var Item $item */
			$itemRev = $lookup->getEntityRevision( $fromId, 0, LookupConstants::LATEST_FROM_MASTER );
			$item = $itemRev->getEntity();
			'@phan-var Item $item';
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
	 * @return array ( Site $site, string $pageName )
	 * @phan-return array{0:Site,1:string}
	 */
	private function getSiteAndNormalizedPageName( SiteList $sites, string $site, string $pageTitle ): array {
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

	private function getAttemptSaveStatus( ?Item $item, Summary $summary, int $flags ): Status {
		if ( $item === null ) {
			// to not have an Item isn't really bad at this point
			return Status::newGood( true );
		} else {
			// Do the actual save, or if it don't exist yet create it.
			return $this->entitySavingHelper->attemptSaveEntity(
				$item,
				$summary,
				$this->extractRequestParams(),
				$this->getContext(),
				$flags
			);
		}
	}

	private function buildResult( ?Item $item, Status $status ): void {
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
	protected function validateParameters( array $params ): void {
		if ( $params['fromsite'] === $params['tosite'] ) {
			$this->errorReporter->dieError( 'The from site cannot match the to site', 'param-illegal' );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode(): bool {
		return true;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken(): string {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		$siteIds = $this->siteLinkGlobalIdentifiersProvider->getList( $this->siteLinkGroups );

		return array_merge( parent::getAllowedParams(), [
			'tosite' => [
				ParamValidator::PARAM_TYPE => $siteIds,
				ParamValidator::PARAM_REQUIRED => true,
			],
			'totitle' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'fromsite' => [
				ParamValidator::PARAM_TYPE => $siteIds,
				ParamValidator::PARAM_REQUIRED => true,
			],
			'fromtitle' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'token' => null,
			'bot' => false,
		] );
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=wblinktitles&fromsite=enwiki&fromtitle=Hydrogen&tosite=dewiki&totitle=Wasserstoff'
			=> 'apihelp-wblinktitles-example-1',
		];
	}

}

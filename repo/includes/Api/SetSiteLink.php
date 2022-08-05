<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiMain;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module to associate a page on a site with a Wikibase entity or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @license GPL-2.0-or-later
 */
class SetSiteLink extends ModifyEntity {

	/**
	 * @var SiteLinkChangeOpFactory
	 */
	private $siteLinkChangeOpFactory;

	/**
	 * @var SiteLinkBadgeChangeOpSerializationValidator
	 */
	private $badgeSerializationValidator;

	/** @var SiteLinkPageNormalizer */
	private $siteLinkPageNormalizer;

	/**
	 * @var SiteLinkTargetProvider
	 */
	protected $siteLinkTargetProvider;

	/**
	 * @var string[]
	 */
	private $sandboxEntityIds;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		SiteLinkChangeOpFactory $siteLinkChangeOpFactory,
		SiteLinkBadgeChangeOpSerializationValidator $badgeSerializationValidator,
		SiteLinkPageNormalizer $siteLinkPageNormalizer,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		bool $federatedPropertiesEnabled,
		array $sandboxEntityIds
	) {
		parent::__construct( $mainModule, $moduleName, $federatedPropertiesEnabled );

		$this->siteLinkChangeOpFactory = $siteLinkChangeOpFactory;
		$this->badgeSerializationValidator = $badgeSerializationValidator;
		$this->siteLinkPageNormalizer = $siteLinkPageNormalizer;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->sandboxEntityIds = $sandboxEntityIds;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		SettingsArray $repoSettings,
		SiteLinkBadgeChangeOpSerializationValidator $siteLinkBadgeChangeOpSerializationValidator,
		SiteLinkPageNormalizer $siteLinkPageNormalizer,
		SiteLinkTargetProvider $siteLinkTargetProvider
	): self {

		return new self(
			$mainModule,
			$moduleName,
			$changeOpFactoryProvider
				->getSiteLinkChangeOpFactory(),
			$siteLinkBadgeChangeOpSerializationValidator,
			$siteLinkPageNormalizer,
			$siteLinkTargetProvider,
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			$repoSettings->getSetting( 'sandboxEntityIds' )
		);
	}

	/**
	 * @see ApiBase::isWriteMode()
	 *
	 * @return bool Always true.
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
	 * Checks whether the link should be removed based on params
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	private function shouldRemove( array $params ): bool {
		if ( $params['linktitle'] === '' || ( !isset( $params['linktitle'] ) && !isset( $params['badges'] ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	protected function modifyEntity( EntityDocument $entity, ChangeOp $changeOp, array $preparedParameters ): Summary {
		if ( !( $entity instanceof Item ) ) {
			$this->errorReporter->dieError( "The given entity is not an item", "not-item" );
		}

		$item = $entity;
		$summary = $this->createSummary( $preparedParameters );
		$linksite = $this->stringNormalizer->trimToNFC( $preparedParameters['linksite'] );
		$hasLinkWithSiteId = $item->getSiteLinkList()->hasLinkWithSiteId( $linksite );
		$resultBuilder = $this->getResultBuilder();

		if ( $this->shouldRemove( $preparedParameters ) ) {
			if ( $hasLinkWithSiteId ) {
				$siteLink = $item->getSiteLinkList()->getBySiteId( $linksite );
				$this->applyChangeOp( $changeOp, $entity, $summary );
				$resultBuilder->addRemovedSiteLinks( new SiteLinkList( [ $siteLink ] ), 'entity' );
			}
		} else {
			try {
				$result = $changeOp->validate( $entity );
				if ( !$result->isValid() ) {
					throw new ChangeOpValidationException( $result );
				}

				$this->applyChangeOp( $changeOp, $entity, $summary );

				$link = $item->getSiteLinkList()->getBySiteId( $linksite );
				$resultBuilder->addSiteLinkList(
					new SiteLinkList( [ $link ] ),
					'entity',
					true // always add the URL
				);
			} catch ( ChangeOpException $ex ) {
				$this->errorReporter->dieException( $ex, 'modification-failed' );
			}
		}

		return $summary;
	}

	protected function getChangeOp( array $preparedParameters, EntityDocument $entity ): ChangeOp {
		if ( $this->shouldRemove( $preparedParameters ) ) {
			$linksite = $this->stringNormalizer->trimToNFC( $preparedParameters['linksite'] );
			return $this->siteLinkChangeOpFactory->newRemoveSiteLinkOp( $linksite );
		} else {
			$linksite = $this->stringNormalizer->trimToNFC( $preparedParameters['linksite'] );
			$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );
			$site = $sites->getSite( $linksite );

			if ( $site === false ) {
				$this->errorReporter->dieError(
					'The supplied site identifier was not recognized',
					'not-recognized-siteid'
				);
			}

			$effectiveLinkTitle = isset( $preparedParameters['linktitle'] )
				? $this->stringNormalizer->trimWhitespace( $preparedParameters['linktitle'] )
				: $this->getLinkTitleFromExistingSiteLink( $entity, $linksite );
			$page = $this->siteLinkPageNormalizer->normalize(
				$site,
				$effectiveLinkTitle,
				$preparedParameters['badges'] ?? []
			);

			if ( $page === false ) {
				$this->errorReporter->dieWithError(
					[ 'wikibase-api-no-external-page', $linksite, $effectiveLinkTitle ],
					'no-external-page'
				);
			}

			$badges = ( isset( $preparedParameters['badges'] ) )
				? $this->parseSiteLinkBadges( $preparedParameters['badges'] )
				: null;

			return $this->siteLinkChangeOpFactory->newSetSiteLinkOp( $linksite, $page, $badges );
		}
	}

	private function getLinkTitleFromExistingSiteLink( EntityDocument $entity, string $linksite ) {
		if ( !( $entity instanceof Item ) ) {
			$this->errorReporter->dieWithError( "The given entity is not an item", "not-item" );
		}
		$item = $entity;
		try {
			$siteLink = $item->getSiteLinkList()->getBySiteId( $linksite );
		} catch ( OutOfBoundsException $ex ) {
			$this->errorReporter->dieWithError(
				[ 'wikibase-validator-no-such-sitelink', $linksite ],
				'no-such-sitelink'
			);
		}
		return $siteLink->getPageName();
	}

	private function parseSiteLinkBadges( array $badges ): array {
		try {
			$this->badgeSerializationValidator->validateBadgeSerialization( $badges );
		} catch ( ChangeOpDeserializationException $exception ) {
			$this->errorReporter->dieException( $exception, $exception->getErrorCode() );
		}

		return $this->getBadgeItemIds( $badges );
	}

	private function getBadgeItemIds( array $badges ): array {
		return array_map( function( $badge ) {
			return new ItemId( $badge );
		}, $badges );
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		$siteIds = $this->siteLinkGlobalIdentifiersProvider->getList( $this->siteLinkGroups );

		return array_merge(
			parent::getAllowedParams(),
			[
				'linksite' => [
					ParamValidator::PARAM_TYPE => $siteIds,
					ParamValidator::PARAM_REQUIRED => true,
				],
				'linktitle' => [
					ParamValidator::PARAM_TYPE => 'string',
				],
				'badges' => [
					ParamValidator::PARAM_TYPE => array_keys( $this->badgeItems ),
					ParamValidator::PARAM_ISMULTI => true,
				],
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$id = $this->sandboxEntityIds[ 'mainItem' ];

		return [
			'action=wbsetsitelink&id=' . $id . '&linksite=enwiki&linktitle=Hydrogen'
			=> [ 'apihelp-wbsetsitelink-example-1', $id ],
			'action=wbsetsitelink&id=' . $id . '&linksite=enwiki&linktitle=Hydrogen&summary=Loves%20Oxygen'
			=> [ 'apihelp-wbsetsitelink-example-2', $id ],
			'action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=dewiki&linktitle=Wasserstoff'
			=> 'apihelp-wbsetsitelink-example-3',
			'action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=dewiki'
			=> 'apihelp-wbsetsitelink-example-4',
			'action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=plwiki&linktitle=Wodór&badges=Q149'
			=> 'apihelp-wbsetsitelink-example-5',
			'action=wbsetsitelink&id=' . $id . '&linksite=plwiki&badges=Q2|Q149'
			=> [ 'apihelp-wbsetsitelink-example-6', $id ],
			'action=wbsetsitelink&id=' . $id . '&linksite=plwiki&linktitle=Warszawa'
			=> [ 'apihelp-wbsetsitelink-example-7', $id ],
			'action=wbsetsitelink&id=' . $id . '&linksite=plwiki&linktitle=Wodór&badges='
			=> [ 'apihelp-wbsetsitelink-example-8', $id ],
		];
	}

}

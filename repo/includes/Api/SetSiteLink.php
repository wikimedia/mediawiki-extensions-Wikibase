<?php

namespace Wikibase\Repo\Api;

use ApiMain;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpSiteLink;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Summary;

/**
 * API module to associate a page on a site with a Wikibase entity or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @license GPL-2.0+
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

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param SiteLinkChangeOpFactory $siteLinkChangeOpFactory
	 * @param SiteLinkBadgeChangeOpSerializationValidator $badgeSerializationValidator
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		SiteLinkChangeOpFactory $siteLinkChangeOpFactory,
		SiteLinkBadgeChangeOpSerializationValidator $badgeSerializationValidator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->siteLinkChangeOpFactory = $siteLinkChangeOpFactory;
		$this->badgeSerializationValidator = $badgeSerializationValidator;
	}

	/**
	 * @see ApiBase::isWriteMode()
	 *
	 * @return bool Always true.
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
	 * Checks whether the link should be removed based on params
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	private function shouldRemove( array $params ) {
		if ( $params['linktitle'] === '' || ( !isset( $params['linktitle'] ) && !isset( $params['badges'] ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @see ModifyEntity::modifyEntity
	 *
	 * @param EntityDocument &$entity
	 * @param array $params
	 * @param int $baseRevId
	 *
	 * @return Summary
	 */
	protected function modifyEntity( EntityDocument &$entity, array $params, $baseRevId ) {
		if ( !( $entity instanceof Item ) ) {
			$this->errorReporter->dieError( "The given entity is not an item", "not-item" );
		}

		$item = $entity;
		$summary = $this->createSummary( $params );
		$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );
		$hasLinkWithSiteId = $item->getSiteLinkList()->hasLinkWithSiteId( $linksite );
		$resultBuilder = $this->getResultBuilder();

		if ( $this->shouldRemove( $params ) ) {
			if ( $hasLinkWithSiteId ) {
				$changeOp = $this->getChangeOp( $params );
				$siteLink = $item->getSiteLinkList()->getBySiteId( $linksite );
				$this->applyChangeOp( $changeOp, $entity, $summary );
				$resultBuilder->addRemovedSiteLinks( new SiteLinkList( array( $siteLink ) ), 'entity' );
			}
		} else {
			try {
				$changeOp = $this->getChangeOp( $params );

				$result = $changeOp->validate( $entity );
				if ( !$result->isValid() ) {
					throw new ChangeOpValidationException( $result );
				}

				$this->applyChangeOp( $changeOp, $entity, $summary );

				$link = $item->getSiteLinkList()->getBySiteId( $linksite );
				$resultBuilder->addSiteLinkList(
					new SiteLinkList( array( $link ) ),
					'entity',
					true // always add the URL
				);
			} catch ( ChangeOpException $ex ) {
				$this->errorReporter->dieException( $ex, 'modification-failed' );
			}
		}

		return $summary;
	}

	/**
	 * @param array $params
	 *
	 * @return ChangeOpSiteLink
	 */
	private function getChangeOp( array $params ) {
		if ( $this->shouldRemove( $params ) ) {
			$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );
			return $this->siteLinkChangeOpFactory->newRemoveSiteLinkOp( $linksite );
		} else {
			$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );
			$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );
			$site = $sites->getSite( $linksite );

			if ( $site === false ) {
				$this->errorReporter->dieError(
					'The supplied site identifier was not recognized',
					'not-recognized-siteid'
				);
			}

			if ( isset( $params['linktitle'] ) ) {
				$page = $site->normalizePageName( $this->stringNormalizer->trimWhitespace( $params['linktitle'] ) );

				if ( $page === false ) {
					$this->errorReporter->dieMessage( 'no-external-page', $linksite, $params['linktitle'] );
				}
			} else {
				$page = null;
			}

			$badges = ( isset( $params['badges'] ) )
				? $this->parseSiteLinkBadges( $params['badges'] )
				: null;

			return $this->siteLinkChangeOpFactory->newSetSiteLinkOp( $linksite, $page, $badges );
		}
	}

	private function parseSiteLinkBadges( array $badges ) {
		try {
			$this->badgeSerializationValidator->validateBadgeSerialization( $badges );
		} catch ( ChangeOpDeserializationException $exception ) {
			$this->errorReporter->dieException( $exception, $exception->getErrorCode() );
		}

		return $this->getBadgeItemIds( $badges );
	}

	private function getBadgeItemIds( array $badges ) {
		return array_map( function( $badge ) {
			return new ItemId( $badge );
		}, $badges );
	}

	/**
	 * @see ModifyEntity::getAllowedParams
	 */
	protected function getAllowedParams() {
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		return array_merge(
			parent::getAllowedParams(),
			array(
				'linksite' => array(
					self::PARAM_TYPE => $sites->getGlobalIdentifiers(),
					self::PARAM_REQUIRED => true,
				),
				'linktitle' => array(
					self::PARAM_TYPE => 'string',
				),
				'badges' => array(
					self::PARAM_TYPE => array_keys( $this->badgeItems ),
					self::PARAM_ISMULTI => true,
				),
			)
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen'
			=> 'apihelp-wbsetsitelink-example-1',
			'action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen&summary=Loves%20Oxygen'
			=> 'apihelp-wbsetsitelink-example-2',
			'action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=dewiki&linktitle=Wasserstoff'
			=> 'apihelp-wbsetsitelink-example-3',
			'action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=dewiki'
			=> 'apihelp-wbsetsitelink-example-4',
			'action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=plwiki&linktitle=Wodór&badges=Q149'
			=> 'apihelp-wbsetsitelink-example-5',
			'action=wbsetsitelink&id=Q42&linksite=plwiki&badges=Q2|Q149'
			=> 'apihelp-wbsetsitelink-example-6',
			'action=wbsetsitelink&id=Q42&linksite=plwiki&linktitle=Warszawa'
			=> 'apihelp-wbsetsitelink-example-7',
			'action=wbsetsitelink&id=Q42&linksite=plwiki&linktitle=Wodór&badges='
			=> 'apihelp-wbsetsitelink-example-8',
		);
	}

}

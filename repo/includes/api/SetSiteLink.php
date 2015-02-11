<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOpSiteLink;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to associate a page on a site with a Wikibase entity or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Michał Łazowik
 * @author Adam Shorland
 */
class SetSiteLink extends ModifyEntity {

	/**
	 * @var SiteLinkChangeOpFactory
	 */
	private $siteLinkChangeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->siteLinkChangeOpFactory = $changeOpFactoryProvider->getSiteLinkChangeOpFactory();
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
	 * @see ModifyEntity::getEntityRevisionFromApiParams
	 */
	protected function getEntityRevisionFromApiParams( array $params ) {
		$entityRev = parent::getEntityRevisionFromApiParams( $params );

		// If we found anything then check if it is of the correct base class
		if ( !is_null( $entityRev ) && !( $entityRev->getEntity() instanceof Item ) ) {
			$this->dieError( 'The content on the found page is not of correct type', 'wrong-class' );
		}

		return $entityRev;
	}

	/**
	 * @see ApiModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( Entity &$entity, array $params, $baseRevId ) {
		wfProfileIn( __METHOD__ );

		if ( !( $entity instanceof Item ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieError( "The given entity is not an item", "not-item" );
		}

		$item = $entity;
		$summary = $this->createSummary( $params );
		$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );

		if ( $this->shouldRemove( $params ) ) {
			if ( $item->hasLinkToSite( $linksite ) ) {
				$link = $item->getSiteLink( $linksite );

				$changeOp = $this->getChangeOp( $params );
				$this->applyChangeOp( $changeOp, $entity, $summary );

				$this->getResultBuilder()->addSiteLinks( array( $link ), 'entity', array( 'removed' ) );
			}
		} else {
			if ( isset( $params['linktitle'] ) || $item->hasLinkToSite( $linksite ) ) {
				$changeOp = $this->getChangeOp( $params );
				$this->applyChangeOp( $changeOp, $entity, $summary );

				$link = $item->getSiteLink( $linksite );
				$this->getResultBuilder()->addSiteLinks( array( $link ), 'entity', array( 'url' ) );
			} else {
				wfProfileOut( __METHOD__ );
				$this->dieMessage( 'no-such-sitelink', $params['linktitle'] );
			}
		}

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @param array $params
	 *
	 * @return ChangeOpSiteLink
	 */
	private function getChangeOp( array $params ) {
		wfProfileIn( __METHOD__ );
		if ( $this->shouldRemove( $params ) ) {
			$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );
			wfProfileOut( __METHOD__ );
			return $this->siteLinkChangeOpFactory->newRemoveSiteLinkOp( $linksite );
		} else {
			$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );
			$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );
			$site = $sites->getSite( $linksite );

			if ( $site === false ) {
				wfProfileOut( __METHOD__ );
				$this->dieError( 'The supplied site identifier was not recognized' , 'not-recognized-siteid' );
			}

			if ( isset( $params['linktitle'] ) ) {
				$page = $site->normalizePageName( $this->stringNormalizer->trimWhitespace( $params['linktitle'] ) );

				if ( $page === false ) {
					wfProfileOut( __METHOD__ );
					$this->dieMessage( 'no-external-page', $linksite, $params['linktitle'] );
				}
			} else {
				$page = null;
			}

			$badges = ( isset( $params['badges'] ) )
				? $this->parseSiteLinkBadges( $params['badges'] )
				: null;

			wfProfileOut( __METHOD__ );
			return $this->siteLinkChangeOpFactory->newSetSiteLinkOp( $linksite, $page, $badges );
		}
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
					ApiBase::PARAM_TYPE => $sites->getGlobalIdentifiers(),
					ApiBase::PARAM_REQUIRED => true,
				),
				'linktitle' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
				'badges' => array(
					ApiBase::PARAM_TYPE => array_keys( $this->badgeItems ),
					ApiBase::PARAM_ISMULTI => true,
				),
			)
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 *
	 * @return array
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen'
			=> 'apihelp-wbsetsitelink-example-1',
			'action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen&summary=World%20domination%20will%20be%20mine%20soon!'
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

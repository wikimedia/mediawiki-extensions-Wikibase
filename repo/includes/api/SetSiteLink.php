<?php

namespace Wikibase\Api;

use Wikibase\ChangeOp\ChangeOpSiteLink;
use ApiBase;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;

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
	 * @since 0.5
	 *
	 * Checks whether the link should be removed based on params
	 *
	 * @param array $params
	 * @return bool
	 */
	protected function shouldRemove( array $params ) {
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
			$this->dieUsage( 'The content on the found page is not of correct type', 'wrong-class' );
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
			$this->dieUsage( "The given entity is not an item", "not-item" );
		}

		$item = $entity;
		$summary = $this->createSummary( $params );
		$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );

		if ( $this->shouldRemove( $params ) ) {
			if ( $item->hasLinkToSite( $linksite ) ) {
				$link = $item->getSiteLink( $linksite );
				$this->getChangeOp( $params )->apply( $item, $summary );
				$this->getResultBuilder()->addSiteLinks( array( $link ), 'entity', array( 'removed' ) );
			}
		} else {
			if ( isset( $params['linktitle'] ) || $item->hasLinkToSite( $linksite ) ) {
				$this->getChangeOp( $params )->apply( $item, $summary );
				$link = $item->getSiteLink( $linksite );
				$this->getResultBuilder()->addSiteLinks( array( $link ), 'entity', array( 'url' ) );
			} else {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( "Cannot modify badges: sitelink to '{$params['linktitle']}' doesn't exist", 'no-such-sitelink' );
			}
		}

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 * @return ChangeOpSiteLink
	 */
	protected function getChangeOp( array $params ) {
		wfProfileIn( __METHOD__ );
		if ( $this->shouldRemove( $params ) ) {
			$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );
			wfProfileOut( __METHOD__ );
			return new ChangeOpSiteLink( $linksite );
		} else {
			$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );
			$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );
			$site = $sites->getSite( $linksite );

			if ( $site === false ) {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( 'The supplied site identifier was not recognized' , 'not-recognized-siteid' );
			}

			if ( isset( $params['linktitle'] ) ) {
				$page = $site->normalizePageName( $this->stringNormalizer->trimWhitespace( $params['linktitle'] ) );

				if ( $page === false ) {
					wfProfileOut( __METHOD__ );
					$this->dieUsage( 'The external client site did not provide page information' , 'no-external-page' );
				}
			} else {
				$page = null;
			}

			$badges = ( isset( $params['badges'] ) )
				? $this->parseSiteLinkBadges( $params['badges'] )
				: null;

			wfProfileOut( __METHOD__ );
			return new ChangeOpSiteLink( $linksite, $page, $badges );
		}
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'wrong-class', 'info' => $this->msg( 'wikibase-api-wrong-class' )->text() ),
			array( 'code' => 'not-item', 'info' => $this->msg( 'wikibase-api-not-item' )->text() ),
			array( 'code' => 'not-recognized-siteid', 'info' => $this->msg( 'wikibase-api-not-recognized-siteid' )->text() ),
			array( 'code' => 'no-external-page', 'info' => $this->msg( 'wikibase-api-no-external-page' )->text() ),
			array( 'code' => 'no-such-sitelink', 'info' => $this->msg( 'wikibase-api-no-sitelink' )->text() ),
		) );
	}

	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		// Experimental setting of badges in api
		// @todo remove experimental once JS UI is in place, (also remove the experimental examples below and TESTS)
		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$experimentalParams = array(
				'badges' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_ISMULTI => true,
				),
			);
		} else {
			$experimentalParams = array();
		}

		return array_merge(
			parent::getAllowedParams(),
			parent::getAllowedParamsForId(),
			parent::getAllowedParamsForSiteLink(),
			parent::getAllowedParamsForEntity(),
			array(
				'linksite' => array(
					ApiBase::PARAM_TYPE => $sites->getGlobalIdentifiers(),
					ApiBase::PARAM_REQUIRED => true,
				),
				'linktitle' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
			),
			$experimentalParams
		);
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {

		// Experimental setting of badges in api
		// @todo remove experimental once JS UI is in place, (also remove the experimental examples below and tests)
		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$experimentalParams = array(
				'badges' => 'The IDs of items to be set as badges. They will replace the current ones. If this parameter is not set, the badges will not be changed',
				'linktitle' => 'The title of the article to link. If this parameter is an empty string or both linktitle and badges are not set, the link will be removed.',
			);
		} else {
			$experimentalParams = array(
				'linktitle' => 'The title of the article to link. If this parameter is an empty the link will be removed.'
			);
		}

		return array_merge(
			parent::getParamDescription(),
			parent::getParamDescriptionForId(),
			parent::getParamDescriptionForSiteLink(),
			parent::getParamDescriptionForEntity(),
			array(
				'linksite' => 'The identifier of the site on which the article to link resides',
			),
			$experimentalParams
		);
	}

	/**
	 * @see \ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to associate an article on a wiki with a Wikibase item or remove an already made such association.'
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		// Experimental setting of badges in api
		// @todo remove experimental once JS UI is in place, (also remove the experimental tests)
		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {

			$experimentalExamples = array(
				'api.php?action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=plwiki&linktitle=Wodór&badges=Q149'
				=> 'Add a sitelink "Wodór" for the Polish page on item with the link for the English page to "Hydrogen" with one badge pointing to the item with id "Q149"',
				'api.php?action=wbsetsitelink&id=Q42&linksite=plwiki&badges=Q2|Q149'
				=> 'Change badges for the link to Polish page from the item with id "Q42" to two badges pointing to the items with ids "Q2" and "Q149" wothout providing the link title',
				'api.php?action=wbsetsitelink&id=Q42&linksite=plwiki&linktitle=Warszawa'
				=> 'Change the link to Polish page from the item with id "Q42" without changing badges',
				'api.php?action=wbsetsitelink&id=Q42&linksite=plwiki&linktitle=Wodór&badges='
				=> 'Change the link to Polish page from the item with id "Q42" and remove all of its badges',
			);
		} else {
			$experimentalExamples = array();
		}

		$examples = array(
			'api.php?action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen'
			=> 'Add a sitelink "Hydrogen" for English page with id "Q42", if the site link does not exist',
			'api.php?action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Add a sitelink "Hydrogen" for English page with id "Q42", if the site link does not exist with an edit summary of "World domination will be mine soon!"',
			'api.php?action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=dewiki&linktitle=Wasserstoff'
			=> 'Add a sitelink "Wasserstoff" for the German page on item with the link for the English page to "Hydrogen", if the site link does not exist',
			'api.php?action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=dewiki'
			=> 'Removes the German sitelink from the item',
		);

		return array_merge( $examples, $experimentalExamples );
	}

}

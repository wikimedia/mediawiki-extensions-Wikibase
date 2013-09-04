<?php

namespace Wikibase\Api;

use Wikibase\ChangeOpSiteLink;
use ApiBase, User;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Utils;

/**
 * API module to associate a page on a site with a Wikibase entity or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Michał Łazowik
 */
class SetSiteLink extends ModifyEntity {

	/**
	 * @see  \Wikibase\Api\ModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$permissions[] = 'sitelink-' . ( strlen( $params['linktitle'] ) ? 'update' : 'remove' );
		return $permissions;
	}

	/**
	 * @see  \Wikibase\Api\ModifyEntity::getEntityContent
	 */
	protected function getEntityContent( array $params ) {
		$entityContent = parent::getEntityContent( $params );

		// If we found anything then check if it is of the correct base class
		if ( !is_null( $entityContent ) && !( $entityContent instanceof ItemContent ) ) {
			$this->dieUsage( 'The content on the found page is not of correct type', 'wrong-class' );
		}

		return $entityContent;
	}
	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		// Note that linksite should always exist as a prerequisite for this
		// call to succeede. The param linktitle will not always exist because
		// that signals a sitelink to remove.
	}

	/**
	 * @see ApiModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( EntityContent &$entityContent, array $params ) {
		wfProfileIn( __METHOD__ );

		if ( !( $entityContent instanceof ItemContent ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "The given entity is not an item", "not-item" );
		}

		$summary = $this->createSummary( $params );
		$item = $entityContent->getItem();
		$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );

		if (
			isset( $params['linksite'] ) &&
			( is_null( $params['linktitle'] ) || $params['linktitle'] === '' ) )
		{
			if ( $item->hasLinkToSite( $linksite ) ) {
				$link = $item->getSimpleSiteLink( $linksite );
				$this->getChangeOp( $params )->apply( $item, $summary );
				$this->addSiteLinksToResult( array( $link ), 'entity', 'sitelinks', 'sitelink', array( 'removed' ) );
			}
		} else {
			$this->getChangeOp( $params )->apply( $item, $summary );
			$link = $item->getSimpleSiteLink( $linksite );
			$this->addSiteLinksToResult( array( $link ), 'entity', 'sitelinks', 'sitelink', array( 'url', 'badges' ) );
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
		if (
			isset( $params['linksite'] ) &&
			( is_null( $params['linktitle'] ) || $params['linktitle'] === '' ) )
		{
			$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );
			wfProfileOut( __METHOD__ );
			return new ChangeOpSiteLink( $linksite, null );
		} else {
			$linksite = $this->stringNormalizer->trimToNFC( $params['linksite'] );
			$sites = $this->getSiteLinkTargetSites();
			$site = $sites->getSite( $linksite );

			if ( $site === false ) {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( 'The supplied site identifier was not recognized' , 'not-recognized-siteid' );
			}

			$page = $site->normalizePageName( $this->stringNormalizer->trimWhitespace( $params['linktitle'] ) );

			if ( $page === false ) {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( 'The external client site did not provide page information' , 'no-external-page' );
			}

			wfProfileOut( __METHOD__ );
			return new ChangeOpSiteLink( $linksite, $page, $params['badges'] );
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
		return array_merge(
			parent::getAllowedParams(),
			parent::getAllowedParamsForId(),
			parent::getAllowedParamsForSiteLink(),
			parent::getAllowedParamsForEntity(),
			array(
				'linksite' => array(
					ApiBase::PARAM_TYPE => $this->getSiteLinkTargetSites()->getGlobalIdentifiers(),
					ApiBase::PARAM_REQUIRED => true,
				),
				'linktitle' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
				'badges' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_ISMULTI => true,
				),
			)
		);
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			parent::getParamDescriptionForId(),
			parent::getParamDescriptionForSiteLink(),
			parent::getParamDescriptionForEntity(),
			array(
				'linksite' => 'The identifier of the site on which the article to link resides',
				'linktitle' => 'The title of the article to link. If this parameter is not set or an empty string, the link will be removed',
				'badges' => 'The IDs of items to be set as badges. They will replace the current ones. If this parameter is not set, the badges will not be changed',
			)
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
		return array(
			'api.php?action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen'
			=> 'Add a sitelink "Hydrogen" for English page with id "Q42", if the site link does not exist',
			'api.php?action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Add a sitelink "Hydrogen" for English page with id "Q42", if the site link does not exist with an edit summary of "World domination will be mine soon!"',
			'api.php?action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=dewiki&linktitle=Wasserstoff'
			=> 'Add a sitelink "Wasserstoff" for the German page on item with the link from the English page to "Hydrogen", if the site link does not exist',
			'api.php?action=wbsetsitelink&site=enwiki&title=Hydrogen&linksite=dewiki'
			=> 'Removes the German sitelink from the item',
		);
	}

}

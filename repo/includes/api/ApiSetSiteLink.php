<?php

namespace Wikibase;
use ApiBase, User, Http;

/**
 * API module to associate a page on a site with a Wikibase entity or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ApiSetSiteLink extends ApiModifyEntity {

	/**
	 * @see  ApiModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$permissions[] = 'sitelink-' . ( strlen( $params['linktitle'] ) ? 'update' : 'remove' );
		return $permissions;
	}

	/**
	 * @see  ApiModifyEntity::findEntity()
	 */
	protected function findEntity( array $params ) {
		$entityContent = parent::findEntity( $params );

		// If we found anything then check if it is of the correct base class
		if ( !is_null( $entityContent ) && !( $entityContent instanceof ItemContent ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-wrong-class' )->text(), 'wrong-class' );
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
	 * @see  ApiModifyEntity::getTextForComment()
	 */
	protected function getTextForComment( array $params, $plural = 1 ) {
		return Autocomment::formatAutoComment(
			'wbsetsitelink-' . ( ( isset( $params['linktitle'] ) && $params['linktitle'] !== "" ) ? "set" : "remove" ),
			array( /*$plural*/ 1, $params['linksite'] )
		);
	}

	/**
	 * @see  ApiModifyEntity::getTextForSummary()
	 */
	protected function getTextForSummary( array $params ) {
		return Autocomment::formatAutoSummary(
			array( $params['linktitle'] )
		);
	}

	/**
	 * @see ApiModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( EntityContent &$entityContent, array $params ) {

		if ( isset( $params['linktitle'] ) ) {
			$params['linktitle'] = Utils::squashToNFC( $params['linktitle'] );
		}

		if ( isset( $params['linksite'] ) && ( $params['linktitle'] === '' ) ) {
			$link = $entityContent->getItem()->getSiteLink( $params['linksite'] );

			if ( !$link ) {
				$this->dieUsage( $this->msg( 'wikibase-api-remove-sitelink-failed' )->text(), 'remove-sitelink-failed' );
			}

			$entityContent->getItem()->removeSiteLink( $params['linksite'] );
			$this->addSiteLinksToResult( array( $link ), 'entity', 'sitelinks', 'sitelink', array( 'removed' ) );
			return true;
		}
		else {
			$sites = $this->getSiteLinkTargetSites();
			$site = $sites->getSite( $params['linksite'] );

			if ( $site === false ) {
				$this->dieUsage( $this->msg( 'wikibase-api-not-recognized-siteid' )->text(), 'not-recognized-siteid' );
			}

			$page = $site->normalizePageName( $params['linktitle'] );

			if ( $page === false ) {
				$this->dieUsage( $this->msg( 'wikibase-api-no-external-page' )->text(), 'no-external-page' );
			}

			$link = new SiteLink( $site, $page );
			$ret = $entityContent->getEntity()->addSiteLink( $link, 'set' );

			if ( $ret === false ) {
				$this->dieUsage( $this->msg( 'wikibase-api-add-sitelink-failed' )->text(), 'add-sitelink-failed' );
			}

			$this->addSiteLinksToResult( array( $ret ), 'entity', 'sitelinks', 'sitelink', array( 'url' ) );
			return $ret !== false;
		}
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'empty-link-title', 'info' => $this->msg( 'wikibase-api-empty-link-title' )->text() ),
			array( 'code' => 'link-exists', 'info' => $this->msg( 'wikibase-api-link-exists' )->text() ),
			array( 'code' => 'database-error', 'info' => $this->msg( 'wikibase-api-database-error' )->text() ),
			array( 'code' => 'add-sitelink-failed', 'info' => $this->msg( 'wikibase-api-add-sitelink-failed' )->text() ),
			array( 'code' => 'remove-sitelink-failed', 'info' => $this->msg( 'wikibase-api-remove-sitelink-failed' )->text() ),
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
				'linktitle' => 'The title of the article to link',
			)
		);
	}

	/**
	 * @see ApiBase::getDescription()
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
			'api.php?action=wbsetsitelink&id=42&linksite=enwiki&linktitle=Wikimedia'
			=> 'Add title "Wikimedia" for English page with id "42" if the site link does not exist',
			'api.php?action=wbsetsitelink&id=42&linksite=enwiki&linktitle=Wikimedia&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Add title "Wikimedia" for English page with id "42", if the site link does not exist',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetsitelink';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}

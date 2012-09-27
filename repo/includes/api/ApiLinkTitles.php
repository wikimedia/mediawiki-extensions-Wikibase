<?php

namespace Wikibase;
use ApiBase, User, Http, Status;

/**
 * API module to associate two pages on two different sites with a Wikibase item .
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 */
class ApiLinkTitles extends Api {

	/**
	 * @see  ApiModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $item, $params );

		$permissions[] = $entity->getType() . '-read';
		$permissions[] = 'linktitles-update';
		return $permissions;
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$user = $this->getUser();

		// This is really already done with needsToken()
		if ( $this->needsToken() && !$user->matchEditToken( $params['token'] ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-session-failure' )->text(), 'session-failure' );
		}

		if ( $params['fromsite'] === $params['tosite'] ) {
			$this->dieUsage( $this->msg( 'wikibase-api-fromsite-eq-tosite' )->text(), 'fromsite-eq-tosite' );
		}

		if ( !( strlen( $params['fromtitle'] ) > 0 && strlen( $params['totitle'] ) > 0 ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-fromtitle-and-totitle' )->text(), 'fromtitle-and-totitle' );
		}

		$sites = $this->getSiteLinkTargetSites();

		// Get all parts for the from-link
		// Site is already tested through allowed params ;)
		$fromSite = $sites->getSite( $params['fromsite'] );
		// This must be tested now
		$fromPage = $fromSite->normalizePageName( $params['fromtitle'] );
		if ( $fromPage === false ) {
			$this->dieUsage( $this->msg( 'wikibase-api-no-external-page' )->text(), 'no-external-page' );
		}
		// This is used for testing purposes later
		$fromId = ItemHandler::singleton()->getIdForSiteLink( $params['fromsite'], $fromPage );

		// Get all part for the to-link
		// Site is already tested through allowed params ;)
		$toSite = $sites->getSite( $params['tosite'] );
		// This must be tested now
		$toPage = $toSite->normalizePageName( $params['totitle'] );
		if ( $toPage === false ) {
			$this->dieUsage( $this->msg( 'wikibase-api-no-external-page' )->text(), 'no-external-page' );
		}
		// This is used for testing purposes later
		$toId = ItemHandler::singleton()->getIdForSiteLink( $params['tosite'], $toPage );

		$return = array();
		$flags = 0;
		$itemContent = null;

		// Figure out which parts to use and what to create anew
		if ( !$fromId && !$toId ) {
			// create new item
			$itemContent = ItemContent::newEmpty();
			$toLink = new SiteLink( $toSite, $toPage );
			$return[] = $itemContent->getItem()->addSiteLink( $toLink, 'set' );
			$fromLink = new SiteLink( $fromSite, $fromPage );
			$return[] = $itemContent->getItem()->addSiteLink( $fromLink, 'set' );

			$flags |= EDIT_NEW;
		}
		elseif ( !$fromId && $toId ) {
			// reuse to-site's item
			$itemContent = ItemHandler::singleton()->getFromId( $toId );
			$fromLink = new SiteLink( $fromSite, $fromPage );
			$return[] = $itemContent->getItem()->addSiteLink( $fromLink, 'set' );
		}
		elseif ( $fromId && !$toId ) {
			// reuse from-site's item
			$itemContent = ItemHandler::singleton()->getFromId( $fromId );
			$toLink = new SiteLink( $toSite, $toPage );
			$return[] = $itemContent->getItem()->addSiteLink( $toLink, 'set' );
		}
		elseif ( $fromId === $toId ) {
			// no-op
			$this->dieUsage( $this->msg( 'wikibase-api-common-item' )->text(), 'common-item' );
		}
		else {
			// dissimilar items
			$this->dieUsage( $this->msg( 'wikibase-api-no-common-item' )->text(), 'no-common-item' );
		}

		$this->addSiteLinksToResult( $return, 'entity' );

		$flags |= ( $user->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;
		$summary = '';

		if ( $itemContent === null ) {
			// to not have an ItemContent isn't really bad at this point
			$edit = null;
			$status = Status::newGood( true );
		}
		else {
			// Do the actual save, or if it don't exist yet create it.
			$edit = new EditEntity( $itemContent, $user );
			$status = $edit->attemptSave( $summary, $flags );
		}

		if ( !$status->isOK() ) {
			$edit->reportApiErrors( $this, 'save-failed' );
		}

		if ( $itemContent !== null ) {
			$this->getResult()->addValue(
				'entity',
				'id', $itemContent->getItem()->getId()
			);
			$this->getResult()->addValue(
				'entity',
				'type', $itemContent->getItem()->getType()
			);
		}

		$this->getResult()->addValue(
			null,
			'success',
			(int)$status->isOK()
		);
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'create-failed', 'info' => $this->msg( 'wikibase-api-create-failed' )->text() ),
			array( 'code' => 'save-failed', 'info' => $this->msg( 'wikibase-api-save-failed' )->text() ),
			array( 'code' => 'session-failure', 'info' => $this->msg( 'wikibase-api-session-failure' )->text() ),
			array( 'code' => 'common-item', 'info' => $this->msg( 'wikibase-api-common-item' )->text() ),
			array( 'code' => 'no-common-item', 'info' => $this->msg( 'wikibase-api-no-common-item' )->text() ),
			array( 'code' => 'no-external-page', 'info' => $this->msg( 'wikibase-api-no-external-page' )->text() ),
			array( 'code' => 'fromtitle-and-totitle', 'info' => $this->msg( 'wikibase-api-fromtitle-and-totitle' )->text() ),
			array( 'code' => 'fromsite-eq-tosite', 'info' => $this->msg( 'wikibase-api-fromsite-eq-tosite' )->text() ),
		) );
	}
	/**
	 * Returns whether this module requires a Token to execute
	 * @return bool
	 */
	public function needsToken() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true ;
	}

	/**
	 * Indicates whether this module must be called with a POST request
	 * @return bool
	 */
	public function mustBePosted() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true ;
	}

	/**
	 * Indicates whether this module requires write mode
	 * @return bool
	 */
	public function isWriteMode() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithWrite' ) : true ;
	}

	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		$sites = $this->getSiteLinkTargetSites();
		return array_merge( parent::getAllowedParams(), array(
			'tosite' => array(
				ApiBase::PARAM_TYPE => $sites->getGlobalIdentifiers(),
			),
			'totitle' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'fromsite' => array(
				ApiBase::PARAM_TYPE => $sites->getGlobalIdentifiers(),
			),
			'fromtitle' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'token' => null,
			'bot' => false,
		) );
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'tosite' => array( 'An identifier for the site on which the page resides.',
				"Use together with 'totitle' to make a complete sitelink."
			),
			'totitle' => array( 'Title of the page to associate.',
				"Use together with 'tosite' to make a complete sitelink."
			),
			'fromsite' => array( 'An identifier for the site on which the page resides.',
				"Use together with 'fromtitle' to make a complete sitelink."
			),
			'fromtitle' => array( 'Title of the page to associate.',
				"Use together with 'fromsite' to make a complete sitelink."
			),
			'token' => array( 'A "edittoken" token previously obtained through the token module (prop=info).',
				'Later it can be implemented a mechanism where a token can be returned spontaneously',
				'and the requester should then start using the new token from the next request, possibly when',
				'repeating a failed request.'
			),
		) );
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module to associate two articles on two different wikis with a Wikibase item.'
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wblinktitles&fromsite=enwiki&fromtitle=Hydrogen&tosite=dewiki&totitle=Wasserstoff'
			=> 'Add a link "Hydrogen" from the English page to "Wasserstoff" at the German page',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wblinktitles';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}

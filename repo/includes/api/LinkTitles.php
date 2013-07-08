<?php

namespace Wikibase\Api;

use ApiBase, User, Status;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\SiteLink;
use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\StoreFactory;
use Wikibase\Summary;
use Wikibase\Settings;

/**
 * API module to associate two pages on two different sites with a Wikibase item .
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 */
class LinkTitles extends ApiWikibase {

	/**
	 * @see  \Wikibase\Api\ModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$permissions[] = 'linktitles-update';
		return $permissions;
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$user = $this->getUser();

		if ( $params['fromsite'] === $params['tosite'] ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'The from site can not match the to site' , 'fromsite-eq-tosite' );
		}

		if ( !( strlen( $params['fromtitle'] ) > 0 && strlen( $params['totitle'] ) > 0 ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'The from title can not match the to title' , 'fromtitle-and-totitle' );
		}

		$sites = $this->getSiteLinkTargetSites();

		// Get all parts for the from-link
		// Site is already tested through allowed params ;)
		$fromSite = $sites->getSite( $params['fromsite'] );
		// This must be tested now
		$fromPage = $fromSite->normalizePageName( $params['fromtitle'] );

		if ( $fromPage === false ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'The external client site did not provide page information' , 'no-external-page' );
		}

		// This is used for testing purposes later
		$fromId = StoreFactory::getStore()->newSiteLinkCache()->getItemIdForLink( $params['fromsite'], $fromPage );

		// Get all part for the to-link
		// Site is already tested through allowed params ;)
		$toSite = $sites->getSite( $params['tosite'] );
		// This must be tested now
		$toPage = $toSite->normalizePageName( $params['totitle'] );

		if ( $toPage === false ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'The external client site did not provide page information' , 'no-external-page' );
		}
		// This is used for testing purposes later
		$toId = StoreFactory::getStore()->newSiteLinkCache()->getItemIdForLink( $params['tosite'], $toPage );

		$return = array();
		$flags = 0;
		$itemContent = null;

		$summary = new Summary( $this->getModuleName() );
		$summary->addAutoSummaryArgs( $fromSite->getGlobalId() . ":$fromPage", $toSite->getGlobalId() . ":$toPage" );

		// Figure out which parts to use and what to create anew
		if ( !$fromId && !$toId ) {
			// create new item
			$itemContent = ItemContent::newEmpty();
			$toLink = new SimpleSiteLink( $toSite->getGlobalId(), $toPage );
			$itemContent->getItem()->addSimpleSiteLink( $toLink );
			$return[] = $toLink;
			$fromLink = new SimpleSiteLink( $fromSite->getGlobalId(), $fromPage );
			$itemContent->getItem()->addSimpleSiteLink( $fromLink );
			$return[] = $fromLink;

			$flags |= EDIT_NEW;
			$summary->setAction( 'create' ); //FIXME: i18n
		}
		elseif ( !$fromId && $toId ) {
			// reuse to-site's item
			$itemContent = EntityContentFactory::singleton()->getFromId(
				new EntityId( Item::ENTITY_TYPE, $toId )
			);
			$fromLink = new SimpleSiteLink( $fromSite->getGlobalId(), $fromPage );
			$itemContent->getItem()->addSimpleSiteLink( $fromLink );
			$return[] = $fromLink;
			$summary->setAction( 'connect' );
		}
		elseif ( $fromId && !$toId ) {
			// reuse from-site's item
			$itemContent = EntityContentFactory::singleton()->getFromId(
				new EntityId( Item::ENTITY_TYPE, $fromId )
			);
			$toLink = new SimpleSiteLink( $toSite->getGlobalId(), $toPage );
			$itemContent->getItem()->addSimpleSiteLink( $toLink );
			$return[] = $toLink;
			$summary->setAction( 'connect' );
		}
		elseif ( $fromId === $toId ) {
			// no-op
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Common item detected', 'common-item' );
		}
		else {
			// dissimilar items
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'No common item detected' , 'no-common-item' );
		}

		$this->addSiteLinksToResult( $return, 'entity' );

		if ( $itemContent === null ) {
			// to not have an ItemContent isn't really bad at this point
			$status = Status::newGood( true );
		}
		else {
			// Do the actual save, or if it don't exist yet create it.
			$status = $this->attemptSaveEntity( $itemContent,
				$summary->toString(),
				$flags );

			$this->addRevisionIdFromStatusToResult( 'entity', 'lastrevid', $status );
		}

		if ( $itemContent !== null ) {
			$this->getResult()->addValue(
				'entity',
				'id', $itemContent->getItem()->getId()->getNumericId()
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

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'fromsite-eq-tosite', 'info' => $this->msg( 'wikibase-api-fromsite-eq-tosite' )->text() ),
			array( 'code' => 'fromtitle-and-totitle', 'info' => $this->msg( 'wikibase-api-fromtitle-and-totitle' )->text() ),
			array( 'code' => 'no-external-page', 'info' => $this->msg( 'wikibase-api-no-external-page' )->text() ),
			array( 'code' => 'common-item', 'info' => $this->msg( 'wikibase-api-common-item' )->text() ),
			array( 'code' => 'no-common-item', 'info' => $this->msg( 'wikibase-api-no-common-item' )->text() ),
		) );
	}

	/**
	 * @see \ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
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
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
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

}

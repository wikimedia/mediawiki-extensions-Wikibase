<?php

namespace Wikibase;
use ApiBase, User;

/**
 * API module to associate a page on a site with a Wikibase item or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseLinkSite.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiLinkSite extends ApiModifyItem {

	/**
	 * Check the rights for the user accessing this module.
	 * This is called from ModifyItem.
	 * 
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 * @param $mod null|String name of the module, usually not set
	 * @param $op null|String operation that is about to be done, usually not set
	 * @return array of errors reported from the static getPermissionsError
	 */
	protected function getPermissionsErrorInternal( $user, array $params, $mod=null, $op=null ) {
		return parent::getPermissionsError( $user, 'site-link', $params['link'] );
	}
	
	/**
	 * Make a string for an auto comment.
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @param $available integer the number of bytes available for the autocomment
	 * @return string that can be used as an auto comment
	 */
	protected function autoComment( array $params, $available=128 ) {
		if ( isset( $params['linktitle'] ) ) {
			$comment = $params['link'] . "-sitelink:" . $params['linksite']
				. SUMMARY_GROUPING
				. ApiModifyItem::pickValuesFromParams( $params, $available-strlen($params['link'] . "-sitelink:" . $params['linksite'] ), 'linktitle' );
		}
		// FIXME: Code for link badges
		else {
			$comment = '';
		}
		return $comment;
	}

	/**
	 * Actually modify the item.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param array $params
	 *
	 * @return boolean Success indicator
	 */
	protected function modifyItem( Item &$item, array $params ) {
		if ( isset($params['link']) && $params['link'] === 'remove') {
			return $item->removeSiteLink( $params['linksite'], $params['linktitle'] );
		}
		else {
			$res = $this->getResult();
			$ret = $item->addSiteLink( $params['linksite'], $params['linktitle'], $params['link'] );
			
			if ( $ret !== false ) {
				$normalized = array();
				if ( $params['linksite'] !== $ret['site'] ) {
					$normalized['linksite'] = array( 'from' => $params['linksite'], 'to' => $ret['site'] );
				}

				if ( $params['linktitle'] !== $ret['title'] ) {
					$normalized['linktitle'] = array( 'from' => $params['linktitle'], 'to' => $ret['title'] );
				}

				// FIXME: Code for link badges

				if ( count($normalized) ) {
					$res->addValue(
						'item',
						'normalized',
						$normalized
					);
				}

				$this->addSiteLinksToResult( array( $ret['site'] => $ret ), 'item' );
			}

			return $ret !== false;
		}
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'link-exists', 'info' => wfMsg( 'wikibase-api-link-exists' ) ),
			array( 'code' => 'database-error', 'info' => wfMsg( 'wikibase-api-database-error' ) ),
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
		$allowedParams = parent::getAllowedParams();
		$allowedParams['item'][ApiBase::PARAM_DFLT] = 'set';
		return array_merge( $allowedParams, array(
			'linkbadge' => array(
				ApiBase::PARAM_TYPE => 'string', // TODO: list? integer? how will badges be represented?
			),
			'linksite' => array(
				ApiBase::PARAM_TYPE => Sites::singleton()->getIdentifiers(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'linktitle' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'link' => array(
				ApiBase::PARAM_TYPE => array( 'add', 'update', 'set', 'remove' ),
				ApiBase::PARAM_DFLT => 'add',
			),
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
			'linksite' => 'The identifier of the site on which the article to link resides',
			'linktitle' => 'The title of the article to link',
			'linkbadge' => 'Badge to give to the page, ie "good" or "featured"',
			'link' => array( 'Indicates if you are adding or removing the link, and in case of adding, if it can or should already exist.',
				"add - the link should not exist before the call or an error will be reported.",
				"update - the link should exist before the call or an error will be reported.",
				"set - the link could exist or not before the call.",
				"remove - the link is removed if its found."
			)
		) );
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module to associate an artiile on a wiki with a Wikibase item or remove an already made such association.'
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wblinksite&id=42&linksite=en&linktitle=Wikimedia'
			=> 'Add title "Wikimedia" for English page with id "42" if the link does not exist',
			'api.php?action=wblinksite&id=42&linksite=en&linktitle=Wikimedia&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Add title "Wikimedia" for English page with id "42" with an edit summary if the link does not exist',
			'api.php?action=wblinksite&id=42&linksite=en&linktitle=Wikimedia&linkbadge='
			=> 'Add title "Wikimedia" for English page with id "42", and with a badge, if the link does not exist',
			'api.php?action=wblinksite&id=42&linksite=en&linktitle=Wikimedia&link=update'
			=> 'Updates title "Wikimedia" for English page with id "42" if the link exist',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wblinksite';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
